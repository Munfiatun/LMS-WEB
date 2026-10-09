<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slide;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\SlidebookService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SlidebookWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private Course $course;

    private CourseSection $section;

    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.provider' => 'mock']);
        Http::preventStrayRequests();
        $this->seed(RoleSeeder::class);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_INSTRUCTOR)->first()->id,
            'is_active' => true,
        ]);
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->course = Course::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $category->id,
            'title' => 'Workflow Course',
            'slug' => 'workflow-course',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $this->section = CourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Chapter 1',
            'order' => 1,
        ]);
        $this->material = LearningMaterial::create([
            'section_id' => $this->section->id,
            'title' => 'Materi Uji',
            'slug' => 'materi-uji',
            'status' => 'draft',
            'order' => 1,
            'content' => 'Konten materi valid untuk AI generation.',
        ]);
    }

    // ───── 1. STATUS BYPASS PREVENTION ─────

    public function test_unapproved_slidebook_cannot_be_published(): void
    {
        $slidebook = $this->reviewSlidebook();

        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.publish', $slidebook))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slidebook');

        $this->assertSame(Slidebook::STATUS_REVIEW, $slidebook->fresh()->status);
    }

    public function test_review_slidebook_cannot_skip_approve_to_published(): void
    {
        $slidebook = $this->reviewSlidebook();

        // Attempt direct publish without approval
        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.publish', $slidebook))
            ->assertUnprocessable();

        // Approve it
        $this->post(route('instructor.slidebooks.approve', $slidebook))->assertSessionHas('success');
        $this->assertSame(Slidebook::STATUS_DRAFT, $slidebook->fresh()->status);
        $this->assertNotNull($slidebook->fresh()->approved_by);

        // Now publish should succeed
        $this->post(route('instructor.slidebooks.publish', $slidebook))->assertSessionHas('success');
        $this->assertSame(Slidebook::STATUS_PUBLISHED, $slidebook->fresh()->status);
    }

    public function test_editing_approved_slidebook_invalidates_approval(): void
    {
        $slidebook = $this->reviewSlidebook();

        $this->actingAs($this->instructor)
            ->post(route('instructor.slidebooks.approve', $slidebook))
            ->assertSessionHas('success');

        $this->assertNotNull($slidebook->fresh()->approved_by);

        // Edit a slide — should reset approval
        $slide = $slidebook->slides()->first();
        $this->put(route('instructor.slides.update', $slide), [
            'title' => 'Edited Slide',
            'content' => 'Modified content',
        ])->assertRedirect();

        $slidebook->refresh();
        $this->assertNull($slidebook->approved_by);
        $this->assertSame(Slidebook::STATUS_REVIEW, $slidebook->status);

        // Must re-approve before publish
        $this->postJson(route('instructor.slidebooks.publish', $slidebook))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slidebook');
    }

    public function test_published_slidebook_cannot_be_directly_edited(): void
    {
        $slidebook = $this->publishSlidebook();

        // Try adding a slide to published version
        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.slides.store', $slidebook), [
                'title' => 'Injected Slide',
                'content' => 'Should be rejected',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slidebook');

        $this->assertSame(1, $slidebook->slides()->count());
    }

    // ───── 2. SAFE REVISION (preserve published while editing draft) ─────

    public function test_revision_creates_draft_copy_without_touching_published_version(): void
    {
        $published = $this->publishSlidebook();
        $originalSlideContent = $published->slides()->first()->content;

        // Create revision
        $this->actingAs($this->instructor)
            ->post(route('instructor.slidebooks.revision', $published))
            ->assertRedirect();

        $revision = $this->material->slidebooks()->where('status', Slidebook::STATUS_DRAFT)->first();
        $this->assertNotNull($revision);
        $this->assertGreaterThan($published->version, $revision->version);
        $this->assertNull($revision->approved_by);
        $this->assertNull($revision->published_at);

        // Published version remains intact
        $published->refresh();
        $this->assertSame(Slidebook::STATUS_PUBLISHED, $published->status);
        $this->assertSame($originalSlideContent, $published->slides()->first()->content);

        // Editing the revision does NOT touch published
        $revisionSlide = $revision->slides()->first();
        app(SlidebookService::class)->updateSlide($revisionSlide, ['content' => 'Revised draft content']);

        $published->refresh();
        $this->assertSame($originalSlideContent, $published->slides()->first()->content);
        $this->assertSame('Revised draft content', $revisionSlide->fresh()->content);
    }

    public function test_duplicate_revision_returns_existing_draft_instead_of_creating_new(): void
    {
        $published = $this->publishSlidebook();

        $this->actingAs($this->instructor)
            ->post(route('instructor.slidebooks.revision', $published))
            ->assertRedirect();

        $firstRevision = $this->material->slidebooks()->where('status', Slidebook::STATUS_DRAFT)->first();

        // Second call should return the same revision, not create another
        $this->post(route('instructor.slidebooks.revision', $published))->assertRedirect();

        $revisions = $this->material->slidebooks()->whereIn('status', ['draft', 'review'])->get();
        $this->assertCount(1, $revisions);
        $this->assertSame($firstRevision->id, $revisions->first()->id);
    }

    public function test_revision_from_non_published_slidebook_is_rejected(): void
    {
        $slidebook = $this->reviewSlidebook();

        try {
            app(SlidebookService::class)->createRevision($slidebook, $this->instructor);
            $this->fail('Should not allow revision from non-published slidebook.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slidebook', $e->errors());
        }
    }

    // ───── 3. FORCE REGENERATE PRESERVES PUBLISHED VERSION ─────

    public function test_force_regenerate_preserves_published_and_archives_old_draft(): void
    {
        // Generate initial slidebook
        $this->actingAs($this->instructor)
            ->post(route('instructor.materials.ai.slidebook', $this->material))
            ->assertRedirect();

        $firstSlidebook = Slidebook::where('material_id', $this->material->id)->first();
        $this->assertSame(Slidebook::STATUS_REVIEW, $firstSlidebook->status);

        // Approve and publish
        $this->post(route('instructor.slidebooks.approve', $firstSlidebook))->assertSessionHas('success');
        $this->post(route('instructor.slidebooks.publish', $firstSlidebook))->assertSessionHas('success');
        $this->assertSame(Slidebook::STATUS_PUBLISHED, $firstSlidebook->fresh()->status);

        // Create a revision (draft)
        $this->post(route('instructor.slidebooks.revision', $firstSlidebook))->assertRedirect();
        $draft = $this->material->slidebooks()->where('status', Slidebook::STATUS_DRAFT)->first();
        $this->assertNotNull($draft);

        // Force regenerate — should archive the draft, keep published, create new review
        $this->post(route('instructor.materials.ai.slidebook', $this->material), ['regenerate' => true])
            ->assertRedirect();

        // Published version is preserved
        $this->assertSame(Slidebook::STATUS_PUBLISHED, $firstSlidebook->fresh()->status);

        // Old draft is archived
        $this->assertSame(Slidebook::STATUS_ARCHIVED, $draft->fresh()->status);

        // New review version was created
        $newReview = $this->material->slidebooks()->where('status', Slidebook::STATUS_REVIEW)->first();
        $this->assertNotNull($newReview);
        $this->assertGreaterThan($draft->version, $newReview->version);
    }

    public function test_force_regenerate_without_published_archives_old_review(): void
    {
        // Generate initial slidebook
        $this->actingAs($this->instructor)
            ->post(route('instructor.materials.ai.slidebook', $this->material))
            ->assertRedirect();

        $firstSlidebook = Slidebook::where('material_id', $this->material->id)->first();
        $this->assertSame(Slidebook::STATUS_REVIEW, $firstSlidebook->status);
        $version1 = $firstSlidebook->version;

        // Force regenerate — old review should be archived, new review created
        $this->post(route('instructor.materials.ai.slidebook', $this->material), ['regenerate' => true])
            ->assertRedirect();

        $this->assertSame(Slidebook::STATUS_ARCHIVED, $firstSlidebook->fresh()->status);
        $newReview = $this->material->slidebooks()->where('status', Slidebook::STATUS_REVIEW)->first();
        $this->assertNotNull($newReview);
        $this->assertGreaterThan($version1, $newReview->version);
    }

    // ───── 4. APPROVAL AND PUBLICATION VALIDATION GATES ─────

    public function test_slidebook_with_empty_slides_cannot_approve(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Empty Slidebook',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.approve', $slidebook))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slidebook');
    }

    public function test_slidebook_with_blank_slide_content_cannot_approve(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Slidebook with empty content',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);
        $slidebook->slides()->create([
            'title' => 'Valid Title',
            'content' => '   ',
            'order' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.approve', $slidebook))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slidebook');
    }

    public function test_publishing_approved_revision_archives_previous_published_version(): void
    {
        $firstPublished = $this->publishSlidebook();
        $this->assertSame(Slidebook::STATUS_PUBLISHED, $firstPublished->fresh()->status);

        // Create revision and push through approval pipeline
        $this->actingAs($this->instructor)
            ->post(route('instructor.slidebooks.revision', $firstPublished))
            ->assertRedirect();

        $revision = $this->material->slidebooks()->where('status', Slidebook::STATUS_DRAFT)->first();
        // Set to review so it can be approved
        $revision->update(['status' => Slidebook::STATUS_REVIEW]);

        $this->post(route('instructor.slidebooks.approve', $revision))->assertSessionHas('success');
        $this->post(route('instructor.slidebooks.publish', $revision))->assertSessionHas('success');

        // New version is published
        $this->assertSame(Slidebook::STATUS_PUBLISHED, $revision->fresh()->status);

        // Old published is now archived
        $this->assertSame(Slidebook::STATUS_ARCHIVED, $firstPublished->fresh()->status);

        // Only one published version exists at a time
        $publishedCount = $this->material->slidebooks()->where('status', Slidebook::STATUS_PUBLISHED)->count();
        $this->assertSame(1, $publishedCount);
    }

    // ───── HELPER METHODS ─────

    private function reviewSlidebook(): Slidebook
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Review Slidebook',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);
        $slidebook->slides()->create([
            'title' => 'Slide Lengkap',
            'content' => 'Konten pembelajaran yang valid dan lengkap.',
            'order' => 1,
            'status' => 'active',
            'needs_review' => false,
        ]);

        return $slidebook;
    }

    private function publishSlidebook(): Slidebook
    {
        $slidebook = $this->reviewSlidebook();

        $this->actingAs($this->instructor);
        $this->post(route('instructor.slidebooks.approve', $slidebook))->assertSessionHas('success');
        $this->post(route('instructor.slidebooks.publish', $slidebook))->assertSessionHas('success');

        return $slidebook->fresh();
    }
}
