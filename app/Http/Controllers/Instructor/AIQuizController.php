<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Slidebook;
use App\Services\AI\AIQuizService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AIQuizController extends Controller
{
    public function store(Request $request, AIQuizService $service): RedirectResponse
    {
        Gate::authorize('create', Quiz::class);
        $data = $request->validate([
            'slidebook_id' => ['required', 'integer', 'exists:slidebooks,id'],
            'total_questions' => ['required', 'integer', 'min:'.AIQuizService::MIN_QUESTIONS, 'max:'.AIQuizService::MAX_QUESTIONS],
            'difficulty' => ['required', 'in:easy,medium,hard,mixed'],
            'type' => ['required', 'in:multiple_choice,true_false'],
            'request_id' => ['required', 'uuid'],
            'custom_instructions' => ['required', 'string', 'min:10', 'max:2000'],
        ], ['total_questions.*' => AIQuizService::QUESTION_COUNT_MESSAGE]);
        $slidebook = Slidebook::with('material.section.course')->findOrFail($data['slidebook_id']);
        Gate::authorize('update', $slidebook);
        $key = 'quiz-generation:'.$request->user()->id.':'.$data['request_id'];
        $timeout = max(60, (int) config('ai.providers.'.config('ai.provider').'.timeout', 60));
        $lock = Cache::lock('quiz-generation:slidebook:'.$slidebook->id, $timeout * 2 + 120);
        if (! $lock->get()) {
            return back()->withInput()->with('error', 'Quiz sedang dibuat. Tunggu hingga proses selesai.');
        }

        try {
            $quiz = Quiz::find(Cache::get($key));
            if ($quiz) {
                Gate::authorize('update', $quiz);
            } else {
                $quiz = $service->generate($slidebook, $request->user(), [
                    'total_questions' => (int) $data['total_questions'],
                    'difficulty' => $data['difficulty'],
                    'type' => $data['type'],
                    'custom_instructions' => $data['custom_instructions'] ?? '',
                ]);
                Cache::put($key, $quiz->id, now()->addDay());
            }

            return redirect()->route('instructor.quizzes.show', $quiz)
                ->with('success', 'AI Generated Draft — periksa soal dan jawaban, lalu terbitkan kuis setelah review.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (ConnectionException $exception) {
            return back()->withInput()->with('error', 'Proses pembuatan Quiz memerlukan waktu terlalu lama. Silakan coba lagi.');
        } catch (Throwable $exception) {
            Log::error('AI Quiz Generation Error', [
                'provider' => config('ai.provider'),
                'model' => config('ai.providers.'.config('ai.provider').'.model'),
                'slidebook_id' => $slidebook->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal membuat quiz dengan AI. Silakan coba lagi.');
        } finally {
            $lock->release();
        }
    }
}
