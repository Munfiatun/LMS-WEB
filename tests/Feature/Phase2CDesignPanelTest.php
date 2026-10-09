<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2CDesignPanelTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): void
    {
        config(['auth.demo_user_password' => 'demo-test-password']);
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(LmsDemoSeeder::class);
    }

    public function test_theme_preview_does_not_persist_design_changes(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();

        $this->assertSame('modern-tech', $slidebook->design_settings['preset'] ?? null);

        $this->actingAs($instructor)
            ->get(route('instructor.slidebooks.preview', [
                'slidebook' => $slidebook,
                'preset' => 'academic-blue',
                'embedded' => 1,
            ]))
            ->assertOk()
            ->assertSee('Preview Siswa');

        $slidebook->refresh();
        $this->assertSame('modern-tech', $slidebook->design_settings['preset'] ?? null);
    }

    public function test_saving_theme_changes_design_only_not_slide_content_or_layout(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->with('slides')->firstOrFail();

        $before = $slidebook->slides
            ->mapWithKeys(fn ($slide) => [$slide->id => [
                'title' => $slide->title,
                'content' => $slide->content,
                'layout' => $slide->layout,
                'order' => $slide->order,
            ]])
            ->all();

        $this->actingAs($instructor)
            ->put(route('instructor.slidebooks.design.update', $slidebook), [
                'preset' => 'academic-blue',
            ])
            ->assertRedirect();

        $slidebook->refresh();
        $this->assertSame('academic-blue', $slidebook->design_settings['preset'] ?? null);

        $after = $slidebook->slides()->orderBy('order')->get()
            ->mapWithKeys(fn ($slide) => [$slide->id => [
                'title' => $slide->title,
                'content' => $slide->content,
                'layout' => $slide->layout,
                'order' => $slide->order,
            ]])
            ->all();

        $this->assertSame($before, $after);
    }

    public function test_theme_update_preserves_existing_design_settings(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();

        $slidebook->update([
            'design_settings' => [
                'preset' => 'modern-tech',
                'spacing' => 'comfortable',
            ],
        ]);

        $this->actingAs($instructor)
            ->put(route('instructor.slidebooks.design.update', $slidebook), [
                'preset' => 'fresh-learning',
            ])
            ->assertRedirect();

        $slidebook->refresh();
        $this->assertSame('fresh-learning', $slidebook->design_settings['preset'] ?? null);
        $this->assertSame('comfortable', $slidebook->design_settings['spacing'] ?? null);
    }

    public function test_invalid_theme_preset_is_rejected_without_mutating_design(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();
        $original = $slidebook->design_settings;

        $this->actingAs($instructor)
            ->from(route('instructor.materials.slidebook.review', $material))
            ->put(route('instructor.slidebooks.design.update', $slidebook), [
                'preset' => 'unknown-theme',
            ])
            ->assertRedirect(route('instructor.materials.slidebook.review', $material))
            ->assertSessionHasErrors('preset');

        $slidebook->refresh();
        $this->assertSame($original, $slidebook->design_settings);
    }

    public function test_other_instructor_cannot_change_slidebook_design(): void
    {
        $this->seedDemo();

        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();
        $original = $slidebook->design_settings;
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();

        $otherInstructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($otherInstructor)
            ->put(route('instructor.slidebooks.design.update', $slidebook), [
                'preset' => 'minimalist',
            ])
            ->assertForbidden();

        $slidebook->refresh();
        $this->assertSame($original, $slidebook->design_settings);
    }

    public function test_published_slidebook_design_cannot_be_changed_directly(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $published = $material->slidebooks()
            ->where('status', Slidebook::STATUS_PUBLISHED)
            ->firstOrFail();
        $original = $published->design_settings;

        $this->actingAs($instructor)
            ->put(route('instructor.slidebooks.design.update', $published), [
                'preset' => 'minimalist',
            ])
            ->assertForbidden();

        $published->refresh();
        $this->assertSame($original, $published->design_settings);
    }
}
