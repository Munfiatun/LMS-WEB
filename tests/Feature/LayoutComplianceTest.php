<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Slide;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\SlideLayoutRegistry;
use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutComplianceTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $student;
    private Course $course;
    private CourseSection $section;
    private LearningMaterial $material;
    private Slidebook $slidebook;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->instructor = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_INSTRUCTOR)->first()->id,
            'is_active' => true,
        ]);
        $this->student = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_STUDENT)->first()->id,
            'is_active' => true,
        ]);
        $this->course = Course::factory()->create(['instructor_id' => $this->instructor->id]);
        $this->section = CourseSection::factory()->create(['course_id' => $this->course->id]);
        $this->material = LearningMaterial::factory()->create(['section_id' => $this->section->id]);
        $this->slidebook = Slidebook::create([
            'title' => 'Test Slidebook',
            'material_id' => $this->material->id,
            'version' => 1,
            'status' => Slidebook::STATUS_DRAFT,
            'created_by' => $this->instructor->id,
        ]);
    }

    public function test_invalid_layout_is_rejected_on_store()
    {
        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.slides.store', $this->slidebook), [
                'title' => 'Test',
                'content' => 'Test content',
                'layout' => 'unknown-malicious-layout',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('layout');
    }

    public function test_valid_layout_is_accepted()
    {
        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.slides.store', $this->slidebook), [
                'title' => 'Test',
                'content' => 'Test content',
                'layout' => 'cover',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('slides', [
            'slidebook_id' => $this->slidebook->id,
            'layout' => 'cover',
        ]);
    }

    public function test_legacy_alias_layout_is_accepted_by_validation()
    {
        $this->actingAs($this->instructor)
            ->postJson(route('instructor.slidebooks.slides.store', $this->slidebook), [
                'title' => 'Test',
                'content' => 'Test content',
                'layout' => 'visual', // legacy alias
            ])
            ->assertRedirect();
        
        $this->assertDatabaseHas('slides', [
            'slidebook_id' => $this->slidebook->id,
            'layout' => 'visual',
        ]);
    }

    public function test_null_layout_preserves_automatic_inference()
    {
        $slide = Slide::create([
            'slidebook_id' => $this->slidebook->id,
            'title' => 'Studi Kasus',
            'content' => 'Test',
            'order' => 1,
            'layout' => null, // Should infer 'example'
        ]);

        $this->actingAs($this->student);
        // It's a draft, so instructor previews
        $response = $this->actingAs($this->instructor)->get(route('instructor.slidebooks.preview', $this->slidebook));
        $response->assertSee('data-layout="example"', false);
    }

    public function test_explicit_layout_overrides_automatic_inference()
    {
        $slide = Slide::create([
            'slidebook_id' => $this->slidebook->id,
            'title' => 'Studi Kasus',
            'content' => 'Test',
            'order' => 1,
            'layout' => 'timeline', // Overrides inference
        ]);

        $response = $this->actingAs($this->instructor)->get(route('instructor.slidebooks.preview', $this->slidebook));
        $response->assertSee('data-layout="timeline"', false);
    }
}
