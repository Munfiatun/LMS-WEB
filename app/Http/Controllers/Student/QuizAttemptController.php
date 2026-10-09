<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\EnrollmentService;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class QuizAttemptController extends Controller
{
    public function __construct(
        private QuizAttemptService $quizAttemptService,
        private EnrollmentService $enrollmentService
    ) {}

    public function show(Quiz $quiz)
    {
        Gate::authorize('view', $quiz);

        $this->enrollmentService->setLastAccessed($quiz->course, auth()->user(), quiz: $quiz);

        $attemptsCount = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', auth()->id())
            ->count();

        $activeAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        return view('student.quizzes.intro', compact('quiz', 'attemptsCount', 'activeAttempt'));
    }

    public function start(Quiz $quiz)
    {
        Gate::authorize('view', $quiz);
        Gate::authorize('create', QuizAttempt::class);

        try {
            $attempt = $this->quizAttemptService->startAttempt($quiz, auth()->user());

            return redirect()->route('student.quizzes.take', ['quiz' => $quiz->id, 'attempt' => $attempt->id]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Proses ujian gagal. Silakan coba kembali.');
        }
    }

    public function take(Quiz $quiz, QuizAttempt $attempt)
    {
        Gate::authorize('view', $quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        Gate::authorize('update', $attempt);

        if ($attempt->status === 'in_progress' && $attempt->expires_at && now()->greaterThanOrEqualTo($attempt->expires_at)) {
            $attempt = $this->quizAttemptService->submitAttempt($attempt, []);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.quizzes.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
                ->with('info', 'Ujian ini sudah selesai.');
        }

        $attempt->load(['attemptQuestions.question', 'attemptQuestions.attemptOptions.option']);

        return view('student.quizzes.take', compact('quiz', 'attempt'));
    }

    public function submit(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        Gate::authorize('view', $quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        Gate::authorize('update', $attempt);

        $validated = $request->validate([
            'answers' => ['array'],
            'answers.*' => ['nullable', 'integer', 'exists:question_options,id'],
        ]);

        try {
            $this->quizAttemptService->submitAttempt($attempt, $validated['answers'] ?? []);

            // Perbarui progress pendaftaran kursus
            $this->enrollmentService->updateProgress($quiz->course, auth()->user());

            return redirect()->route('student.quizzes.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id])
                ->with('success', 'Ujian berhasil dikumpulkan.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Proses ujian gagal. Silakan coba kembali.');
        }
    }

    public function result(Quiz $quiz, QuizAttempt $attempt)
    {
        Gate::authorize('view', $quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        Gate::authorize('view', $attempt);
        abort_if($attempt->status === 'in_progress', 403);

        return view('student.quizzes.result', compact('quiz', 'attempt'));
    }
}
