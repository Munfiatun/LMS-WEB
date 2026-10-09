<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\AI\AISlidebookService;
use App\Services\SlidebookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlidebookVersionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['ai.provider' => 'openai', 'ai.providers.openai.api_key' => 'test-key']);
    }

    public function test_normal_generation_is_idempotent_and_forced_generation_calls_provider_again(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::sequence()->push($this->providerResponse('V1'))->push($this->providerResponse('V2'))]);
        $service = app(AISlidebookService::class);
        $first = $service->generateSlidebookForMaterial($material, $teacher);
        $this->assertSame('review', $first->status);
        $this->assertNull($first->approved_by);
        $this->assertSame($first->id, $service->generateSlidebookForMaterial($material, $teacher)->id);
        Http::assertSentCount(1);
        $second = $service->generateSlidebookForMaterial($material, $teacher, true);
        $this->assertSame(2, $second->version);
        $this->assertSame('V2', $second->title);
        $this->assertSame('review', $second->status);
        $this->assertDatabaseCount('slidebooks', 2);
        Http::assertSentCount(2);
    }

    public function test_published_version_remains_visible_until_revision_is_approved_and_published(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        $published = $this->published($material, $teacher);
        $oldSlide = $published->slides()->sole();
        $this->actingAs($teacher)->post(route('instructor.slidebooks.revision', $published))->assertSessionHas('success');
        $revision = $material->slidebooks()->where('status', 'draft')->sole();
        $this->assertSame(2, $revision->version);
        $this->assertNull($revision->approved_by);
        $this->post(route('instructor.slidebooks.revision', $published))->assertSessionHas('success');
        $this->assertDatabaseCount('slidebooks', 2);
        $this->put(route('instructor.slides.update', $revision->slides()->sole()), ['title' => 'Revised', 'content' => 'Revision content'])->assertSessionHas('success');
        $this->assertSame('Original content', $oldSlide->fresh()->content);
        $this->assertSame($published->id, $material->fresh()->publishedSlidebook->id);
        $this->actingAs($student)->get(route('student.materials.show', [$course, $material]))->assertOk()
            ->assertSee(route('student.slidebooks.show', $published))->assertDontSee(route('student.slidebooks.show', $revision));
        $this->get(route('student.slidebooks.show', $published))->assertOk()->assertSee('Original content');
        $this->get(route('student.slidebooks.show', $revision))->assertForbidden();
        $this->actingAs($teacher)->postJson(route('instructor.slidebooks.publish', $revision))->assertUnprocessable()->assertJsonValidationErrors('slidebook');
        $this->post(route('instructor.slidebooks.approve', $revision))->assertSessionHas('success');
        $this->post(route('instructor.slidebooks.publish', $revision))->assertSessionHas('success');
        $this->assertSame('archived', $published->fresh()->status);
        $this->assertSame('published', $revision->fresh()->status);
        $this->assertSame($revision->id, $material->fresh()->publishedSlidebook->id);
        $this->actingAs($student)->get(route('student.slidebooks.show', $revision))->assertOk()->assertSee('Revision content');
        $this->get(route('student.slidebooks.show', $published))->assertForbidden();
    }

    public function test_published_slides_reject_direct_edit_delete_add_reorder_and_reapproval(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        $book = $this->published($material, $teacher);
        $slide = $book->slides()->sole();
        $this->actingAs($teacher)->putJson(route('instructor.slides.update', $slide), ['title' => 'Bad', 'content' => 'Bad'])->assertUnprocessable();
        $this->deleteJson(route('instructor.slides.destroy', $slide))->assertUnprocessable();
        $this->postJson(route('instructor.slidebooks.slides.store', $book), ['title' => 'Bad', 'content' => 'Bad'])->assertUnprocessable();
        $this->postJson(route('instructor.slidebooks.slides.reorder', $book), ['slide_ids' => [$slide->id]])->assertUnprocessable();
        $this->postJson(route('instructor.slidebooks.approve', $book))->assertUnprocessable();
        $this->assertSame('published', $book->fresh()->status);
        $this->assertSame('Original content', $slide->fresh()->content);
        $this->assertSame(1, $book->slides()->count());
        $this->get(route('instructor.materials.slidebook.review', $material))->assertOk()->assertSee('Create Revision')->assertDontSee('title="Edit Slide"', false);
    }

    public function test_editing_approved_draft_invalidates_approval_and_hides_publish_button(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        $book = $this->published($material, $teacher);
        $revision = app(SlidebookService::class)->createRevision($book, $teacher);
        $this->actingAs($teacher)->post(route('instructor.slidebooks.approve', $revision))->assertSessionHas('success');
        $this->get(route('instructor.materials.slidebook.review', $material))->assertSee('Rilis ke Siswa (Publish)');
        $this->put(route('instructor.slides.update', $revision->slides()->sole()), ['title' => 'Edited', 'content' => 'Changed after approval'])->assertSessionHas('success');
        $this->assertNull($revision->fresh()->approved_by);
        $this->postJson(route('instructor.slidebooks.publish', $revision))->assertUnprocessable();
        $this->get(route('instructor.materials.slidebook.review', $material))->assertDontSee('Rilis ke Siswa (Publish)');
        $this->assertSame('published', $book->fresh()->status);
    }

    public function test_forced_generation_keeps_published_version_and_creates_review_revision(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::sequence()->push($this->providerResponse('First'))->push($this->providerResponse('Forced'))]);
        $first = app(AISlidebookService::class)->generateSlidebookForMaterial($material, $teacher);
        app(SlidebookService::class)->approve($first, $teacher);
        app(SlidebookService::class)->publish($first);
        $revision = app(AISlidebookService::class)->generateSlidebookForMaterial($material, $teacher, true);
        $this->assertSame('review', $revision->status);
        $this->assertSame('Forced', $revision->title);
        $this->assertSame('published', $first->fresh()->status);
        $this->assertSame($first->id, $material->fresh()->publishedSlidebook->id);
        $this->actingAs($student)->get(route('student.slidebooks.show', $first))->assertOk()->assertSee('First');
        $this->get(route('student.slidebooks.show', $revision))->assertForbidden();
        Http::assertSentCount(2);
    }

    public function test_failed_generation_does_not_change_published_content(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        $book = $this->published($material, $teacher);
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'private provider detail'], 500)]);
        $this->actingAs($teacher)->post(route('instructor.materials.ai.slidebook', $material), ['regenerate' => true])
            ->assertSessionHas('error', 'Proses AI gagal. Silakan coba kembali.');
        $this->assertDatabaseCount('slidebooks', 1);
        $this->assertSame('published', $book->fresh()->status);
        $this->assertSame('published', $material->fresh()->status);
        $this->actingAs($student)->get(route('student.slidebooks.show', $book))->assertOk()->assertSee('Original content');
        Http::assertSentCount(1);
    }

    public function test_empty_generated_output_cannot_create_or_publish_slidebook(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode(['title' => 'Empty', 'slides' => []])]]]])]);
        $this->actingAs($teacher)->postJson(route('instructor.materials.ai.slidebook', $material))
            ->assertUnprocessable()->assertJsonValidationErrors('slidebook');
        $this->assertDatabaseCount('slidebooks', 0);
        $this->assertDatabaseHas('ai_processing_logs', ['status' => 'failed']);
    }

    public function test_generation_lock_rejects_duplicate_in_flight_request(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        $lock = Cache::lock('slidebook-generation:'.$material->id, 60);
        $lock->get();
        try {
            $this->actingAs($teacher)->postJson(route('instructor.materials.ai.slidebook', $material))->assertUnprocessable();
            $this->assertDatabaseCount('slidebooks', 0);
            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_foreign_slide_ids_cannot_be_reordered_into_revision(): void
    {
        [$teacher, $student, $course, $material] = $this->source();
        $published = $this->published($material, $teacher);
        $revision = app(SlidebookService::class)->createRevision($published, $teacher);
        $this->actingAs($teacher)->postJson(route('instructor.slidebooks.slides.reorder', $revision), ['slide_ids' => [$published->slides()->sole()->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('slide_ids');
        $this->assertSame(1, $revision->slides()->sole()->order);
    }

    /** @return array{User, User, Course, LearningMaterial} */
    private function source(): array
    {
        $teacher = $this->user('instructor');
        $student = $this->user('student');
        $course = Course::factory()->create(['instructor_id' => $teacher->id, 'status' => 'published']);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id, 'status' => 'draft', 'content' => 'Source lesson content']);
        $course->enrollments()->create(['student_id' => $student->id, 'status' => 'active']);

        return [$teacher, $student, $course, $material];
    }

    private function published(LearningMaterial $material, User $teacher): Slidebook
    {
        $material->update(['status' => 'published']);
        $book = $material->slidebooks()->create(['title' => 'Published V1', 'status' => 'published', 'version' => 1, 'created_by' => $teacher->id, 'approved_by' => $teacher->id]);
        $book->slides()->create(['title' => 'Original', 'content' => 'Original content', 'order' => 1, 'status' => 'active']);

        return $book;
    }

    /** @return array{choices: list<array{message: array{content: string}}>} */
    private function providerResponse(string $title): array
    {
        return ['choices' => [['message' => ['content' => json_encode(['title' => $title, 'slides' => [['title' => $title, 'content' => 'Lesson '.$title, 'order' => 1, 'needs_review' => true]]])]]]];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role], ['label' => $role])->id, 'is_active' => true]);
    }
}
