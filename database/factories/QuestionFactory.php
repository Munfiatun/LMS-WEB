<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_bank_id' => 1,
            'type' => 'multiple_choice',
            'question_text' => $this->faker->paragraph,
            'needs_review' => false,
        ];
    }
}
