<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuestionBank;
use App\Services\Quiz\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function __construct(private QuizService $quizService)
    {
    }

    public function index()
    {
        Gate::authorize('viewAny', Quiz::class);
        $quizzes = Quiz::whereHas('course', function ($q) {
            $q->where('instructor_id', auth()->id());
        })->with('course')->latest()->paginate(10);
        
        $courses = Course::where('instructor_id', auth()->id())->get();

        return view('instructor.quizzes.index', compact('quizzes', 'courses'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Quiz::class);
        
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'section_id' => ['nullable', 'exists:course_sections,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'total_questions' => ['required', 'integer', 'min:1'],
            'randomize_questions' => ['boolean'],
            'randomize_options' => ['boolean'],
            'max_attempts' => ['required', 'integer', 'min:1'],
        ]);

        $course = Course::findOrFail($validated['course_id']);
        
        if ($course->instructor_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->quizService->createQuiz($course, $validated);

        return redirect()->route('instructor.quizzes.index')->with('success', 'Kuis berhasil dibuat.');
    }

    public function show(Quiz $quiz)
    {
        Gate::authorize('view', $quiz);
        $quiz->load(['quizQuestions.question', 'course']);
        return view('instructor.quizzes.show', compact('quiz'));
    }

    public function builder(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);
        $quiz->load(['quizQuestions.question']);
        
        $questionBanks = QuestionBank::where('instructor_id', auth()->id())
            ->with(['questions' => function($q) {
                $q->where('needs_review', false);
            }])->get();

        return view('instructor.quizzes.builder', compact('quiz', 'questionBanks'));
    }

    public function syncQuestions(Request $request, Quiz $quiz)
    {
        Gate::authorize('update', $quiz);
        
        $validated = $request->validate([
            'questions' => ['required', 'array'],
            'questions.*.id' => ['required', 'exists:questions,id'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.order' => ['required', 'integer', 'min:1'],
        ]);

        $this->quizService->syncQuestions($quiz, $validated['questions']);

        return redirect()->route('instructor.quizzes.show', $quiz)->with('success', 'Soal berhasil disinkronkan.');
    }

    public function publish(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);
        
        if ($quiz->quizQuestions()->count() < $quiz->total_questions) {
            return back()->with('error', "Jumlah soal yang dipilih (" . $quiz->quizQuestions()->count() . ") masih kurang dari target total soal ({$quiz->total_questions}).");
        }
        
        $this->quizService->publishQuiz($quiz);
        return back()->with('success', 'Kuis berhasil diterbitkan.');
    }
}
