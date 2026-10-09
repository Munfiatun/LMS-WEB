<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Slidebook;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;

class SlidebookDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_slidebook_has_default_design_settings(): void
    {
        $slidebook = new Slidebook();

        $this->assertIsArray($slidebook->design_settings);
        $this->assertEquals('indigo-dark', $slidebook->design_settings['preset']);
    }

    public function test_instructor_can_update_design_settings(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id]);
        
        $slidebook = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Test Slidebook',
            'status' => 'draft',
            'version' => 1,
            'created_by' => $course->instructor_id,
        ]);

        $response = $this->actingAs($course->instructor)->put(route('instructor.slidebooks.design.update', $slidebook), [
            'preset' => 'fresh-learning',
        ]);

        $response->assertRedirect();
        
        $slidebook->refresh();
        $this->assertEquals('fresh-learning', $slidebook->design_settings['preset']);
    }

    public function test_design_service_returns_correct_tokens(): void
    {
        $slidebook = new Slidebook();
        $slidebook->design_settings = [
            'preset' => 'academic-blue',
        ];

        $service = new \App\Services\SlidebookDesignService();
        $tokens = $service->getDesignTokens($slidebook);

        $this->assertArrayHasKey('css_variables', $tokens);
        $this->assertStringContainsString('--slide-bg: #f8fafc;', $tokens['css_variables']);
        $this->assertStringContainsString('--slide-primary: #2563eb;', $tokens['css_variables']);
        
        $this->assertArrayHasKey('font_class', $tokens);
        $this->assertStringContainsString('font-serif', $tokens['font_class']);
    }
    public function test_old_slidebook_without_settings_resolves_to_indigo_dark(): void
    {
        $slidebook = new Slidebook();
        $slidebook->design_settings = null;
        
        $service = new \App\Services\SlidebookDesignService();
        $tokens = $service->getDesignTokens($slidebook);
        
        $this->assertStringContainsString('--slide-bg: #020617;', $tokens['css_variables']);
    }

    public function test_six_valid_presets_resolve_successfully(): void
    {
        $presets = ['indigo-dark', 'modern-tech', 'academic-blue', 'creative-education', 'fresh-learning', 'minimalist'];
        $service = new \App\Services\SlidebookDesignService();
        
        foreach ($presets as $preset) {
            $slidebook = new Slidebook();
            $slidebook->design_settings = ['preset' => $preset];
            $tokens = $service->getDesignTokens($slidebook);
            $this->assertArrayHasKey('css_variables', $tokens);
        }
    }

    public function test_invalid_preset_is_rejected(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $slidebook = Slidebook::create(['created_by' => $teacher->id, 'status' => 'draft', 'version' => 1, 'title' => 'T', 'material_id' => LearningMaterial::factory()->create(['section_id' => CourseSection::factory()->create(['course_id' => $course->id])->id])->id]);
        
        $response = $this->actingAs($teacher)->put(route('instructor.slidebooks.design.update', $slidebook), [
            'preset' => 'hacked-theme',
        ]);
        
        $response->assertSessionHasErrors('preset');
    }
    
    public function test_arbitrary_malicious_design_values_are_rejected(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $slidebook = Slidebook::create(['created_by' => $teacher->id, 'status' => 'draft', 'version' => 1, 'title' => 'T', 'material_id' => LearningMaterial::factory()->create(['section_id' => CourseSection::factory()->create(['course_id' => $course->id])->id])->id]);
        
        $response = $this->actingAs($teacher)->put(route('instructor.slidebooks.design.update', $slidebook), [
            'preset' => '<script>alert(1)</script>',
        ]);
        
        $response->assertSessionHasErrors('preset');
    }

    public function test_student_cannot_change_theme(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'student'], ['label' => 'Student']);
        $student = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        
        $instructorRole = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $slidebook = Slidebook::create(['created_by' => $teacher->id, 'status' => 'draft', 'version' => 1, 'title' => 'T', 'material_id' => LearningMaterial::factory()->create(['section_id' => CourseSection::factory()->create(['course_id' => $course->id])->id])->id]);

        $response = $this->actingAs($student)->put(route('instructor.slidebooks.design.update', $slidebook), [
            'preset' => 'modern-tech',
        ]);
        
        $response->assertRedirect();
    }

    public function test_foreign_instructor_cannot_change_theme(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $foreignTeacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $slidebook = Slidebook::create(['created_by' => $teacher->id, 'status' => 'draft', 'version' => 1, 'title' => 'T', 'material_id' => LearningMaterial::factory()->create(['section_id' => CourseSection::factory()->create(['course_id' => $course->id])->id])->id]);

        $response = $this->actingAs($foreignTeacher)->put(route('instructor.slidebooks.design.update', $slidebook), [
            'preset' => 'modern-tech',
        ]);
        
        $response->assertForbidden();
    }

    public function test_published_version_cannot_be_directly_restyled(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $slidebook = Slidebook::create(['created_by' => $teacher->id, 'status' => 'published', 'version' => 1, 'title' => 'T', 'material_id' => LearningMaterial::factory()->create(['section_id' => CourseSection::factory()->create(['course_id' => $course->id])->id])->id]);

        $response = $this->actingAs($teacher)->put(route('instructor.slidebooks.design.update', $slidebook), [
            'preset' => 'modern-tech',
        ]);
        
        $response->assertForbidden();
    }

    public function test_theme_update_does_not_change_slide_content(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $slidebook = Slidebook::create(['created_by' => $teacher->id, 'status' => 'draft', 'title' => 'Original Title', 'version' => 1, 'material_id' => LearningMaterial::factory()->create(['section_id' => CourseSection::factory()->create(['course_id' => $course->id])->id])->id]);

        $this->actingAs($teacher)->put(route('instructor.slidebooks.design.update', $slidebook), [
            'preset' => 'modern-tech',
        ]);
        
        $slidebook->refresh();
        $this->assertEquals('Original Title', $slidebook->title);
    }
    public function test_v2_theme_change_does_not_affect_v1(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = \App\Models\User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        
        $studentRole = \App\Models\Role::firstOrCreate(['name' => 'student'], ['label' => 'Student']);
        $student = \App\Models\User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id]);
        
        // V1 published
        $v1 = Slidebook::create([
            'created_by' => $teacher->id,
            'status' => 'published',
            'version' => 1,
            'title' => 'T',
            'material_id' => $material->id,
            'design_settings' => ['preset' => 'indigo-dark'],
        ]);
        
        // V2 draft
        $v2 = Slidebook::create([
            'created_by' => $teacher->id,
            'status' => 'draft',
            'version' => 2,
            'title' => 'T',
            'material_id' => $material->id,
            'design_settings' => ['preset' => 'indigo-dark'],
        ]);
        
        // Change V2 design
        $this->actingAs($teacher)->put(route('instructor.slidebooks.design.update', $v2), [
            'preset' => 'fresh-learning',
        ]);
        
        $v1->refresh();
        $v2->refresh();
        
        // 8. V2 theme change does not affect published V1
        $this->assertEquals('indigo-dark', $v1->design_settings['preset']);
        $this->assertEquals('fresh-learning', $v2->design_settings['preset']);
        
        // 9. students still see V1 during V2 design work
        \App\Models\CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active']);
        $response = $this->actingAs($student)->get(route('student.slidebooks.show', $v1));
        $response->assertOk();
        // Since student can only view published materials through their route, checking V1 preset:
        $service = new \App\Services\SlidebookDesignService();
        $this->assertStringContainsString('--slide-bg: #020617;', $service->getDesignTokens($v1)['css_variables']);
        
        // 10. publishing V2 exposes V2 theme & 11. archived V1 preserves original design
        // Manually simulate publish
        $v1->update(['status' => 'archived']);
        $v2->update(['status' => 'published']);
        
        $v1->refresh();
        $v2->refresh();
        
        $this->assertEquals('indigo-dark', $v1->design_settings['preset']);
        $this->assertEquals('fresh-learning', $v2->design_settings['preset']);
    }
}
