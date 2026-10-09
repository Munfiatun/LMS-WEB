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
        $enrolledCourseIds = CourseEnrollment::where('student_id', $request->user()->id)
            ->whereIn('status', ['active', 'completed'])->pluck('course_id');

        $quizzes = Quiz::whereIn('course_id', $enrolledCourseIds)
            ->available()->whereHas('course', fn ($query) => $query->published())
            ->with('course')
            ->latest()
            ->paginate(10);

        $quizIds = $quizzes->pluck('id');
        $attempts = QuizAttempt::where('student_id', $request->user()->id)
            ->whereIn('quiz_id', $quizIds)
            ->get()
            ->groupBy('quiz_id');

        return view('student.quizzes.index', compact('quizzes', 'attempts'));
    }
}
