<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\MaterialDocument;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $otherInstructor;

    private User $admin;

    private User $student;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $adminRole = Role::where('name', Role::ROLE_ADMIN)->firstOrFail();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();

        $this->instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $this->otherInstructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $this->category = Category::create(['name' => 'Programming', 'slug' => 'programming']);
    }

    // =========================================================================
    // Course CRUD (Instructor)
    // =========================================================================

    public function test_instructor_can_view_courses_index(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.index'))
            ->assertOk();
    }

    public function test_instructor_can_create_course(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.create'))
            ->assertOk();

        $response = $this->actingAs($this->instructor)->post(route('instructor.courses.store'), [
            'title' => 'Laravel Advanced',
            'category_id' => $this->category->id,
            'description' => 'A comprehensive Laravel course.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'title' => 'Laravel Advanced',
            'instructor_id' => $this->instructor->id,
        ]);
    }

    public function test_instructor_can_edit_own_course(): void
    {
        $course = $this->createCourse($this->instructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.edit', $course))
            ->assertOk()
            ->assertSee($course->title);
    }

    public function test_instructor_can_update_own_course(): void
    {
        $course = $this->createCourse($this->instructor);

        $response = $this->actingAs($this->instructor)->put(route('instructor.courses.update', $course), [
            'title' => 'Updated Title',
            'category_id' => $this->category->id,
            'description' => 'Updated description.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_instructor_can_delete_own_course(): void
    {
        $course = $this->createCourse($this->instructor);

        $response = $this->actingAs($this->instructor)
            ->delete(route('instructor.courses.destroy', $course));

        $response->assertRedirect(route('instructor.courses.index'));
        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_instructor_cannot_edit_other_instructors_course(): void
    {
        $course = $this->createCourse($this->otherInstructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.edit', $course))
            ->assertForbidden();
    }

    public function test_instructor_cannot_update_other_instructors_course(): void
    {
        $course = $this->createCourse($this->otherInstructor);

        $this->actingAs($this->instructor)->put(route('instructor.courses.update', $course), [
            'title' => 'Hacked Title',
            'category_id' => $this->category->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('courses', ['title' => 'Hacked Title']);
    }

    public function test_student_cannot_access_instructor_course_routes(): void
    {
        $this->actingAs($this->student)
            ->get(route('instructor.courses.index'))
            ->assertRedirect();
    }

    // =========================================================================
    // Course Publishing
    // =========================================================================

    public function test_instructor_can_publish_course_with_materials(): void
    {
        $course = $this->createCourse($this->instructor);
        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Bab 1',
            'order' => 1,
        ]);
        LearningMaterial::create([
            'section_id' => $section->id,
            'title' => 'Materi 1',
            'content' => 'Konten yang siap dipelajari.',
            'status' => 'published',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.courses.publish', $course));

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    public function test_instructor_cannot_publish_course_without_materials(): void
    {
        $course = $this->createCourse($this->instructor);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.courses.publish', $course));

        $response->assertRedirect();
        $response->assertSessionHasErrors('course');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => Course::STATUS_DRAFT,
        ]);
    }

    // =========================================================================
    // Section Management
    // =========================================================================

    public function test_instructor_can_add_section_to_own_course(): void
    {
        $course = $this->createCourse($this->instructor);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.sections.store', $course), [
                'title' => 'Bab 1: Introduction',
                'description' => 'Pengantar materi',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('course_sections', [
            'course_id' => $course->id,
            'title' => 'Bab 1: Introduction',
        ]);
    }

    public function test_instructor_can_delete_section(): void
    {
        $course = $this->createCourse($this->instructor);
        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Bab to Delete',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->instructor)
            ->delete(route('instructor.sections.destroy', $section));

        $response->assertRedirect();
        $this->assertDatabaseMissing('course_sections', ['id' => $section->id]);
    }

    // =========================================================================
    // Material Management
    // =========================================================================

    public function test_instructor_can_create_material(): void
    {
        $course = $this->createCourse($this->instructor);
        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Bab 1',
            'order' => 1,
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.materials.create', $section))
            ->assertOk();

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.materials.store', $section), [
                'title' => 'Pengenalan Service Layer',
                'description' => 'Materi tentang service layer',
                'duration_minutes' => 15,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('learning_materials', [
            'section_id' => $section->id,
            'title' => 'Pengenalan Service Layer',
        ]);
    }

    public function test_instructor_can_edit_material(): void
    {
        [$course, $section, $material] = $this->createFullMaterial($this->instructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.materials.edit', $material))
            ->assertOk()
            ->assertSee($material->title);
    }

    public function test_instructor_can_update_material(): void
    {
        [$course, $section, $material] = $this->createFullMaterial($this->instructor);

        $response = $this->actingAs($this->instructor)
            ->put(route('instructor.materials.update', $material), [
                'title' => 'Updated Material Title',
                'duration_minutes' => 30,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'title' => 'Updated Material Title',
        ]);
    }

    public function test_instructor_can_delete_material(): void
    {
        [$course, $section, $material] = $this->createFullMaterial($this->instructor);

        $response = $this->actingAs($this->instructor)
            ->delete(route('instructor.materials.destroy', $material));

        $response->assertRedirect();
        $this->assertSoftDeleted('learning_materials', ['id' => $material->id]);
    }

    public function test_instructor_cannot_edit_other_instructors_material(): void
    {
        [$course, $section, $material] = $this->createFullMaterial($this->otherInstructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.materials.edit', $material))
            ->assertForbidden();
    }

    // =========================================================================
    // Document Upload
    // =========================================================================

    public function test_instructor_can_upload_document_to_own_material(): void
    {
        Storage::fake('private');

        [$course, $section, $material] = $this->createFullMaterial($this->instructor);
        $file = UploadedFile::fake()->create('modul-1.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.materials.documents.store', $material), [
                'document' => $file,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('material_documents', [
            'material_id' => $material->id,
            'extension' => 'pdf',
        ]);
    }

    public function test_instructor_can_delete_document(): void
    {
        Storage::fake('private');

        [$course, $section, $material] = $this->createFullMaterial($this->instructor);
        $file = UploadedFile::fake()->create('to-delete.pdf', 500, 'application/pdf');

        $this->actingAs($this->instructor)
            ->post(route('instructor.materials.documents.store', $material), [
                'document' => $file,
            ]);

        $document = MaterialDocument::where('material_id', $material->id)->firstOrFail();

        $response = $this->actingAs($this->instructor)
            ->delete(route('instructor.documents.destroy', $document));

        $response->assertRedirect();
        $this->assertDatabaseMissing('material_documents', ['id' => $document->id]);
    }

    // =========================================================================
    // Admin Category Management
    // =========================================================================

    public function test_admin_can_view_categories(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Programming');
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Data Science',
                'description' => 'Kategori data science',
                'icon' => '📊',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Data Science', 'slug' => 'data-science']);
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $emptyCategory = Category::create(['name' => 'Empty Cat', 'slug' => 'empty-cat']);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $emptyCategory));

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $emptyCategory->id]);
    }

    public function test_student_cannot_access_admin_categories(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.categories.index'))
            ->assertRedirect();
    }

    // =========================================================================
    // Public Course Catalog
    // =========================================================================

    public function test_public_catalog_shows_published_courses(): void
    {
        $publishedCourse = $this->createCourse($this->instructor, Course::STATUS_PUBLISHED);
        $draftCourse = $this->createCourse($this->instructor, Course::STATUS_DRAFT);

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee($publishedCourse->title)
            ->assertDontSee($draftCourse->title);
    }

    public function test_public_catalog_can_filter_by_category(): void
    {
        $otherCategory = Category::create(['name' => 'Design', 'slug' => 'design']);
        $progCourse = $this->createCourse($this->instructor, Course::STATUS_PUBLISHED, $this->category->id);
        $designCourse = $this->createCourse($this->instructor, Course::STATUS_PUBLISHED, $otherCategory->id);

        $this->get(route('courses.index', ['category' => 'programming']))
            ->assertOk()
            ->assertSee($progCourse->title)
            ->assertDontSee($designCourse->title);
    }

    public function test_public_catalog_can_search_courses(): void
    {
        $course = $this->createCourse($this->instructor, Course::STATUS_PUBLISHED);

        $this->get(route('courses.index', ['search' => $course->title]))
            ->assertOk()
            ->assertSee($course->title);
    }

    public function test_public_course_detail_shows_published_course(): void
    {
        $course = $this->createCourse($this->instructor, Course::STATUS_PUBLISHED);
        $section = CourseSection::create(['course_id' => $course->id, 'title' => 'Bab 1', 'order' => 1]);
        LearningMaterial::create([
            'section_id' => $section->id,
            'title' => 'Materi Published',
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'order' => 1,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee($course->title)
            ->assertSee('Bab 1')
            ->assertSee('Materi Published');
    }

    public function test_public_course_detail_returns_404_for_draft(): void
    {
        $draftCourse = $this->createCourse($this->instructor, Course::STATUS_DRAFT);

        $this->get(route('courses.show', $draftCourse->slug))
            ->assertNotFound();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createCourse(User $instructor, string $status = Course::STATUS_DRAFT, ?int $categoryId = null): Course
    {
        return Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $categoryId ?? $this->category->id,
            'title' => 'Course '.fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'status' => $status,
            'published_at' => $status === Course::STATUS_PUBLISHED ? now() : null,
        ]);
    }

    /**
     * @return array{0: Course, 1: CourseSection, 2: LearningMaterial}
     */
    private function createFullMaterial(User $instructor): array
    {
        $course = $this->createCourse($instructor);
        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Test Section',
            'order' => 1,
        ]);
        $material = LearningMaterial::create([
            'section_id' => $section->id,
            'title' => 'Test Material '.fake()->unique()->word(),
            'description' => 'Test description',
            'duration_minutes' => 10,
            'order' => 1,
        ]);

        return [$course, $section, $material];
    }

    /**
     * Test that creating a course with a status field is prohibited.
     */
    public function test_instructor_cannot_set_status_on_course_creation(): void
    {
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.courses.store'), [
                'title' => 'Protected Course',
                'category_id' => $this->category->id,
                'description' => 'Should stay draft.',
                'status' => Course::STATUS_PUBLISHED,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('courses', ['title' => 'Protected Course', 'status' => Course::STATUS_PUBLISHED]);
    }

    /**
     * Test that updating a course with a status field is prohibited.
     */
    public function test_instructor_cannot_update_status_on_course(): void
    {
        $course = $this->createCourse($this->instructor);
        $response = $this->actingAs($this->instructor)
            ->put(route('instructor.courses.update', $course), [
                'title' => $course->title,
                'category_id' => $this->category->id,
                'description' => $course->description,
                'status' => Course::STATUS_PUBLISHED,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => Course::STATUS_DRAFT]);
    }

    /**
     * Test that creating a material with a status field is prohibited.
     */
    public function test_instructor_cannot_set_status_on_material_creation(): void
    {
        $course = $this->createCourse($this->instructor);
        $section = CourseSection::create(['course_id' => $course->id, 'title' => 'Sec', 'order' => 1]);
        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.materials.store', $section), [
                'title' => 'Material with status',
                'description' => 'Desc',
                'status' => LearningMaterial::STATUS_PUBLISHED,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('learning_materials', ['title' => 'Material with status', 'status' => LearningMaterial::STATUS_PUBLISHED]);
    }

    /**
     * Test that updating a material with a status field is prohibited.
     */
    public function test_instructor_cannot_update_status_on_material(): void
    {
        [$course, $section, $material] = $this->createFullMaterial($this->instructor);
        $response = $this->actingAs($this->instructor)
            ->put(route('instructor.materials.update', $material), [
                'title' => $material->title,
                'description' => $material->description,
                'status' => LearningMaterial::STATUS_PUBLISHED,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');
        $material->refresh();
        $this->assertEquals(LearningMaterial::STATUS_DRAFT, $material->status);
    }
}
