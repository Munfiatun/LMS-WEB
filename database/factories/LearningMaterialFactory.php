<?php

namespace Database\Factories;

use App\Models\CourseSection;
use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LearningMaterial>
 */
class LearningMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => CourseSection::factory(),
            'title' => $this->faker->sentence(4),
            'slug' => Str::slug($this->faker->sentence(4)),
            'description' => $this->faker->paragraph(),
            'content' => $this->faker->text(),
            'duration_minutes' => 10,
            'order' => 1,
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
