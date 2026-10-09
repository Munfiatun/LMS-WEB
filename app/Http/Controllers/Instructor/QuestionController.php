<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Services\Quiz\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function __construct(private QuizService $quizService) {}

    /**
     * Store a newly created question with options in the bank.
     */
    public function store(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('create', [Question::class, $questionBank]);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'topic' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'points' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'list', 'min:2'],
            'options.*' => ['required', 'string', 'distinct:ignore_case'],
            'correct_option' => ['required', 'integer', 'min:0', 'max:'.(count($request->array('options')) - 1)],
        ]);

        DB::transaction(function () use ($questionBank, $validated): void {
            $maxOrder = (int) $questionBank->questions()->max('order');

            $question = Question::create([
                'question_bank_id' => $questionBank->id,
                'question_text' => $validated['question_text'],
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'topic' => $validated['topic'] ?? 'Umum',
                'difficulty' => $validated['difficulty'],
                'points' => (int) $validated['points'],
                'explanation' => $validated['explanation'] ?? null,
                'order' => $maxOrder + 1,
                'needs_review' => false,
                'answer_source' => Question::SOURCE_MANUAL,
                'status' => Question::STATUS_APPROVED,
            ]);

            $correctIndex = (int) $validated['correct_option'];
            foreach ($validated['options'] as $index => $optionText) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'is_correct' => ($index === $correctIndex),
                    'order' => $index + 1,
                ]);
            }
        });

        return back()->with('success', 'Butir soal baru berhasil ditambahkan ke bank soal.');
    }

    /**
     * Update an existing question and its options.
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('update', $question);
        $this->quizService->assertQuestionEditable($question);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'topic' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'points' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'list', 'min:2'],
            'options.*' => ['required', 'string', 'distinct:ignore_case'],
            'correct_option' => ['required', 'integer', 'min:0', 'max:'.(count($request->array('options')) - 1)],
        ]);

        DB::transaction(function () use ($question, $validated): void {
            $question->update([
                'question_text' => $validated['question_text'],
                'topic' => $validated['topic'] ?? $question->topic,
                'difficulty' => $validated['difficulty'],
                'points' => (int) $validated['points'],
                'explanation' => $validated['explanation'] ?? null,
                'needs_review' => false, // Review verified on manual edit
                'status' => Question::STATUS_APPROVED,
            ]);

            // Replace options
            $question->options()->delete();

            $correctIndex = (int) $validated['correct_option'];
            foreach ($validated['options'] as $index => $optionText) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'is_correct' => ($index === $correctIndex),
                    'order' => $index + 1,
                ]);
            }
        });

        return back()->with('success', "Soal #{$question->order} berhasil diperbarui.");
    }

    /**
     * Delete a question from the bank.
     */
    public function destroy(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('delete', $question);
        $this->quizService->assertQuestionEditable($question);

        $bank = $question->questionBank;
        $order = $question->order;
        $question->delete();

        // Re-order remaining questions
        $bank->questions()->where('order', '>', $order)->decrement('order');

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Approve/verify a question flagged as needs_review.
     */
    public function approve(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('approve', $question);
        $this->quizService->assertQuestionEditable($question);

        $question->update([
            'needs_review' => false,
            'status' => Question::STATUS_APPROVED,
        ]);

        return back()->with('success', "Kunci jawaban soal #{$question->order} telah diverifikasi dan disetujui.");
    }
}
