<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $enrolledCourseIds = CourseEnrollment::where('student_id', $student->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id');

        $quizzes = Quiz::whereIn('course_id', $enrolledCourseIds)
            ->available()
            ->whereHas('course', fn ($query) => $query->published())
            ->with('course')
            ->latest()
            ->paginate(10);

        $quizIds = $quizzes->pluck('id');
        $attempts = QuizAttempt::where('student_id', $student->id)
            ->whereIn('quiz_id', $quizIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('quiz_id');

        $submittedAttempts = QuizAttempt::query()
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->whereHas('quiz', fn ($query) => $query
                ->available()
                ->whereIn('course_id', $enrolledCourseIds)
                ->whereHas('course', fn ($courseQuery) => $courseQuery->published()))
            ->with('quiz.course')
            ->latest('submitted_at')
            ->get();

        $passedQuizIds = $submittedAttempts
            ->filter(fn (QuizAttempt $attempt) => $attempt->percentage !== null
                && $attempt->percentage >= (float) $attempt->quiz->passing_score)
            ->pluck('quiz_id')
            ->unique();

        $stats = [
            'available_quizzes' => Quiz::whereIn('course_id', $enrolledCourseIds)
                ->available()
                ->whereHas('course', fn ($query) => $query->published())
                ->count(),
            'attempted_quizzes' => $submittedAttempts->pluck('quiz_id')->unique()->count(),
            'passed_quizzes' => $passedQuizIds->count(),
            'submitted_attempts' => $submittedAttempts->count(),
            'average_score' => round((float) ($submittedAttempts->whereNotNull('percentage')->avg('percentage') ?? 0), 1),
        ];

        $recentAttempts = $submittedAttempts->take(5);

        return view('student.quizzes.index', compact('quizzes', 'attempts', 'stats', 'recentAttempts'));
    }
}
