<?php

namespace App\Services\Quiz;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createQuiz(Course $course, array $data): Quiz
    {
        $data['course_id'] = $course->id;
        $this->validateSection($data);

        return $course->quizzes()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $this->validateSection(array_merge($quiz->only(['course_id', 'section_id']), $data));
        $quiz->update($data);

        return $quiz;
    }

    /** @param array<string, mixed> $data */
    private function validateSection(array $data): void
    {
        Validator::make($data, [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'section_id' => ['nullable', 'integer', Rule::exists('course_sections', 'id')->where('course_id', $data['course_id'])],
        ])->validate();
    }

    /**
     * Sync questions to quiz with their specific points and order
     *
     * @param  array<int, array{id: int, points: int, order: int}>  $questionsData
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

            $quiz->update(['total_questions' => count($questionsData), 'status' => 'draft', 'published_at' => null]);
        });
    }

    public function publishQuiz(Quiz $quiz): void
    {
        $questions = $quiz->questions()->with('options')->get();
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['quiz' => 'Quiz harus memiliki minimal satu soal.']);
        }
        foreach ($questions as $question) {
            if (trim(strip_tags($question->question_text)) === ''
                || ! in_array($question->type, ['multiple_choice', 'true_false'], true)
                || $question->needs_review || $question->status !== 'approved'
                || $question->options->count() < 2
                || ($question->type === 'true_false' && $question->options->count() !== 2)
                || $question->options->contains(fn ($option): bool => trim($option->option_text) === '')
                || $question->options->where('is_correct', true)->count() !== 1
                || $question->options->map(fn ($option): string => mb_strtolower(trim($option->option_text)))->unique()->count() !== $question->options->count()) {
                throw ValidationException::withMessages(['quiz' => 'Periksa dan verifikasi semua soal serta kunci jawaban sebelum menerbitkan Quiz.']);
            }
        }
        $quiz->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
