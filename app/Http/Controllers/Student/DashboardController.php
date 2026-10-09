<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrollments = $user->courseEnrollments()->whereHas('course', fn ($query) => $query->published())->whereIn('status', ['active', 'completed'])->with('course.category')->get();

        $stats = [
            'enrolled_courses' => $enrollments->count(),
            'completed_courses' => $enrollments->where('status', 'completed')->count(),
            'completed_materials' => $user->materialProgress()->where('status', 'completed')->count(),
            'quiz_attempts' => QuizAttempt::where('student_id', $user->id)->count(),
            'average_score' => QuizAttempt::where('student_id', $user->id)->where('status', 'submitted')->avg('percentage') ?? 0,
        ];

        return view('student.dashboard', compact('user', 'stats', 'enrollments'));
    }
}
