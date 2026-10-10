<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2DAuthoringTest extends TestCase
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

    public function test_changing_layout_preserves_slide_content_and_order(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();
        $slide = $slidebook->slides()->orderBy('order')->firstOrFail();

        $before = [
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'content' => $slide->content,
            'summary' => $slide->summary,
            'order' => $slide->order,
        ];

        $this->actingAs($instructor)
            ->put(route('instructor.slides.update', $slide), [
                ...$before,
                'layout' => 'comparison',
            ])
            ->assertRedirect();

        $slide->refresh();

        $this->assertSame('comparison', $slide->layout);
        $this->assertSame($before['title'], $slide->title);
        $this->assertSame($before['subtitle'], $slide->subtitle);
        $this->assertSame($before['content'], $slide->content);
        $this->assertSame($before['summary'], $slide->summary);
        $this->assertSame($before['order'], $slide->order);
    }

    public function test_invalid_layout_is_rejected(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();
        $slide = $slidebook->slides()->orderBy('order')->firstOrFail();

        $this->actingAs($instructor)
            ->putJson(route('instructor.slides.update', $slide), [
                'title' => $slide->title,
                'content' => $slide->content,
                'layout' => 'not-a-layout',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('layout');
    }

    public function test_other_instructor_cannot_edit_slide(): void
    {
        $this->seedDemo();

        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();
        $slide = $slidebook->slides()->orderBy('order')->firstOrFail();
        $role = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();

        $otherInstructor = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($otherInstructor)
            ->put(route('instructor.slides.update', $slide), [
                'title' => $slide->title,
                'content' => $slide->content,
                'layout' => 'reading',
            ])
            ->assertForbidden();
    }

    public function test_published_slide_cannot_be_edited_directly(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $published = $material->slidebooks()->where('status', 'published')->firstOrFail();
        $slide = $published->slides()->orderBy('order')->firstOrFail();
        $originalLayout = $slide->layout;

        $this->actingAs($instructor)
            ->putJson(route('instructor.slides.update', $slide), [
                'title' => $slide->title,
                'content' => $slide->content,
                'layout' => 'comparison',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slidebook');

        $this->assertSame($originalLayout, $slide->fresh()->layout);
    }

    public function test_reordering_preserves_slide_content_and_layout(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebooks()->latest('version')->firstOrFail();
        $slides = $slidebook->slides()->orderBy('order')->get();

        $before = $slides->mapWithKeys(fn ($slide) => [$slide->id => [
            'title' => $slide->title,
            'content' => $slide->content,
            'layout' => $slide->layout,
        ]])->all();

        $ids = $slides->pluck('id')->all();
        [$ids[0], $ids[1]] = [$ids[1], $ids[0]];

        $this->actingAs($instructor)
            ->postJson(route('instructor.slidebooks.slides.reorder', $slidebook), [
                'slide_ids' => $ids,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        foreach ($before as $slideId => $snapshot) {
            $slide = $slidebook->slides()->findOrFail($slideId);
            $this->assertSame($snapshot['title'], $slide->title);
            $this->assertSame($snapshot['content'], $slide->content);
            $this->assertSame($snapshot['layout'], $slide->layout);
        }
    }
}
