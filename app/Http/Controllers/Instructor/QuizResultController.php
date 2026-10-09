<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Support\Facades\Gate;

class QuizResultController extends Controller
{
    public function index(Quiz $quiz)
    {
        Gate::authorize('view', $quiz);

        $attempts = $quiz->attempts()->with(['student', 'quiz'])->latest('submitted_at')->paginate(15);

        return view('instructor.quizzes.results', compact('quiz', 'attempts'));
    }
}
