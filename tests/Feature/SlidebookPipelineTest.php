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
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlidebookPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $student;

    protected Course $course;

    protected CourseSection $section;

    protected LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.provider' => 'mock']);
        Http::preventStrayRequests();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->first();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->first();

        $this->instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $this->otherInstructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $category = Category::create(['name' => 'Pemrograman Web', 'slug' => 'pemrograman-web']);

        $this->course = Course::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $category->id,
            'title' => 'Mastering Laravel LCMS',
            'slug' => 'mastering-laravel-lcms',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->section = CourseSection::create([
            'course_id' => $this->course->id,
            'title' => 'Pengenalan LCMS',
            'order' => 1,
        ]);

        $this->material = LearningMaterial::create([
            'section_id' => $this->section->id,
            'title' => 'Arsitektur Bersih pada LCMS',
            'slug' => 'arsitektur-bersih-pada-lcms',
            'status' => 'draft',
            'duration_minutes' => 15,
            'order' => 1,
            'content' => "Arsitektur bersih memisahkan logika domain dari infrastruktur dan presentation layer.\n\nPrinsip ini menjaga kode tetap dapat diuji secara independen.",
        ]);
    }

    public function test_instructor_can_trigger_ai_slidebook_generation_for_own_material(): void
    {
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.materials.ai.slidebook', $this->material));

        $response->assertRedirect(route('instructor.materials.slidebook.review', $this->material));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('slidebooks', [
            'material_id' => $this->material->id,
            'status' => Slidebook::STATUS_REVIEW,
            'created_by' => $this->instructor->id,
        ]);

        $slidebook = Slidebook::where('material_id', $this->material->id)->first();
        $this->assertNotNull($slidebook);
        $this->assertGreaterThan(0, $slidebook->slides()->count());
    }

    public function test_instructor_cannot_trigger_ai_slidebook_for_other_instructors_material(): void
    {
        $response = $this->actingAs($this->otherInstructor)
            ->post(route('instructor.materials.ai.slidebook', $this->material));

        $response->assertForbidden();
    }

    public function test_instructor_can_view_slidebook_review_page(): void
    {
        // Generate slidebook first
        $this->actingAs($this->instructor)
            ->post(route('instructor.materials.ai.slidebook', $this->material));

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.materials.slidebook.review', $this->material));

        $response->assertOk();
        $response->assertViewIs('instructor.slidebooks.review');
        $response->assertSee('Review Slidebook:');
        $response->assertSee('Teks Sumber Dokumen');
    }

    public function test_instructor_cannot_view_slidebook_review_for_others_material(): void
    {
        // Generate slidebook first
        $this->actingAs($this->instructor)
            ->post(route('instructor.materials.ai.slidebook', $this->material));

        $response = $this->actingAs($this->otherInstructor)
            ->get(route('instructor.materials.slidebook.review', $this->material));

        $response->assertForbidden();
    }

    public function test_instructor_can_approve_slidebook(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Draft Slidebook',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.slidebooks.approve', $slidebook));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('slidebooks', [
            'id' => $slidebook->id,
            'status' => Slidebook::STATUS_DRAFT,
            'approved_by' => $this->instructor->id,
        ]);
    }

    public function test_instructor_can_publish_slidebook(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Draft Slidebook',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.slidebooks.publish', $slidebook));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('slidebooks', [
            'id' => $slidebook->id,
            'status' => Slidebook::STATUS_PUBLISHED,
            'approved_by' => $this->instructor->id,
        ]);

        $this->assertDatabaseHas('learning_materials', [
            'id' => $this->material->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
    }

    public function test_instructor_can_add_edit_and_delete_slides(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Test Slidebook',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);

        // 1. Add slide
        $addResponse = $this->actingAs($this->instructor)
            ->post(route('instructor.slidebooks.slides.store', $slidebook), [
                'title' => 'Slide Baru 1',
                'subtitle' => 'Sub judul',
                'content' => 'Isi konten pengantar slide.',
                'summary' => 'Intisari penting.',
            ]);

        $addResponse->assertRedirect();
        $this->assertDatabaseHas('slides', [
            'slidebook_id' => $slidebook->id,
            'title' => 'Slide Baru 1',
        ]);

        $slide = Slide::where('slidebook_id', $slidebook->id)->first();

        // 2. Update slide
        $updateResponse = $this->actingAs($this->instructor)
            ->put(route('instructor.slides.update', $slide), [
                'title' => 'Slide Terupdate',
                'subtitle' => 'Sub judul baru',
                'content' => 'Konten revisi guru.',
                'summary' => 'Intisari revisi.',
                'needs_review' => '0',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('slides', [
            'id' => $slide->id,
            'title' => 'Slide Terupdate',
        ]);

        // 3. Delete slide
        $deleteResponse = $this->actingAs($this->instructor)
            ->delete(route('instructor.slides.destroy', $slide));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('slides', [
            'id' => $slide->id,
        ]);
    }

    public function test_student_cannot_view_unpublished_slidebook(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Review Slidebook',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.slidebooks.show', $slidebook));

        $response->assertForbidden();
    }

    public function test_student_can_view_published_slidebook(): void
    {
        $this->material->update(['status' => 'published']);
        $this->course->enrollments()->create(['student_id' => $this->student->id, 'status' => 'active']);

        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Published Slidebook',
            'status' => Slidebook::STATUS_PUBLISHED,
            'version' => 1,
            'created_by' => $this->instructor->id,
            'published_at' => now(),
        ]);

        Slide::create([
            'slidebook_id' => $slidebook->id,
            'title' => 'Slide Pembelajaran 1',
            'content' => 'Materi slide presentasi siswa.',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.slidebooks.show', $slidebook));

        $response->assertOk();
        $response->assertViewIs('student.slidebooks.show');
        $response->assertSee('Published Slidebook');
        $response->assertSee('Slide Pembelajaran 1');
    }

    public function test_student_and_teacher_preview_share_all_presentation_patterns_without_changing_source(): void
    {
        $this->material->update(['status' => 'published']);
        $this->course->enrollments()->create(['student_id' => $this->student->id, 'status' => 'active']);

        $book = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Presentasi lintas format',
            'status' => Slidebook::STATUS_PUBLISHED,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);
        $examples = [
            ['Pembuka', 'Konsep pengantar.', 'concept'],
            ['Poin penting', "- Definisi pertama\n- Definisi kedua", 'key-points'],
            ['Alur kerja', "1. Mulai\n2. Proses\n3. Selesai", 'process'],
            ['A vs B', "- A: Sifat pertama\n- B: Sifat kedua", 'comparison'],
            ['Kode HTML', "```html\n<script>alert('xss')</script>\n```", 'code'],
            ['Contoh penerapan', 'Contoh dari guru.', 'example'],
            ['Diagram', "![Diagram guru](/storage/diagram.png)\n\nPenjelasan lengkap.", 'visual'],
            ['Rangkuman', '- Intisari sumber.', 'summary'],
            ['Cek pemahaman', "Apa jawabannya?\nA. Satu\nB. Dua", 'checkpoint'],
            ['Kutipan', '> Teks kutipan sumber.', 'quote'],
            ['Bacaan', "## Bagian satu\nPenjelasan.\n\nINFO: Catatan sumber.", 'reading'],
            ['Tabel', "| Sisi A | Sisi B |\n| --- | --- |\n| Isi A | Isi B |", 'comparison'],
        ];
        foreach ($examples as $index => [$title, $content]) {
            $book->slides()->create(['title' => $title, 'content' => $content, 'order' => $index + 1]);
        }
        $original = $book->slides()->get()->toArray();

        $student = $this->actingAs($this->student)->get(route('student.slidebooks.show', $book))->assertOk();
        $teacher = $this->actingAs($this->instructor)->get(route('instructor.slidebooks.preview', $book))->assertOk();

        foreach ($examples as [$title, $content, $layout]) {
            $student->assertSee('data-layout="'.$layout.'"', false)->assertSee($title)->assertSee($content);
            $teacher->assertSee('data-layout="'.$layout.'"', false);
        }
        $student->assertDontSee("<script>alert('xss')</script>", false);
        $teacher->assertSee('Preview Siswa');
        preg_match_all('/<article\b.*?<\/article>/s', $student->getContent(), $studentSlides);
        preg_match_all('/<article\b.*?<\/article>/s', $teacher->getContent(), $teacherSlides);
        $this->assertSame($studentSlides[0], $teacherSlides[0]);
        $this->assertSame($original, $book->slides()->get()->toArray());
        $this->assertTrue($this->instructor->fresh()->isInstructor());
    }

    public function test_owner_can_preview_private_empty_slidebook_and_other_teacher_cannot(): void
    {
        $book = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Draft kosong',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->instructor)->get(route('instructor.slidebooks.preview', $book))
            ->assertOk()->assertSee('Belum ada slide');
        $this->actingAs($this->otherInstructor)->get(route('instructor.slidebooks.preview', $book))->assertForbidden();
        $this->actingAs($this->student)->get(route('instructor.slidebooks.preview', $book))
            ->assertRedirect(route('student.dashboard'));
    }
}
