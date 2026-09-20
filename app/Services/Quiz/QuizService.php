<?php

namespace App\Services\Quiz;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class QuizService
{
    /**
     * @param array<string, mixed> $data
     */
    public function createQuiz(Course $course, array $data): Quiz
    {
        return $course->quizzes()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $quiz->update($data);
        return $quiz;
    }

    /**
     * Sync questions to quiz with their specific points and order
     *
     * @param array<int, array{id: int, points: int, order: int}> $questionsData
     */
    public function syncQuestions(Quiz $quiz, array $questionsData): void
    {
        DB::transaction(function () use ($quiz, $questionsData) {
            $quiz->quizQuestions()->delete();
            
            foreach ($questionsData as $data) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_id' => $data['id'],
                    'points' => $data['points'] ?? 10,
                    'order' => $data['order'] ?? 1,
                ]);
            }
            
            $quiz->update(['total_questions' => count($questionsData)]);
        });
    }

    public function publishQuiz(Quiz $quiz): void
    {
        $quiz->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
