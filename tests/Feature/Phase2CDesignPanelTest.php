<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
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
