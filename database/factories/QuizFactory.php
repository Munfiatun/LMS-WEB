<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => 1,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'passing_score' => 70,
            'duration_minutes' => 60,
            'total_questions' => 10,
            'randomize_questions' => true,
            'randomize_options' => true,
            'max_attempts' => 1,
            'status' => 'draft',
        ];
    }
}
