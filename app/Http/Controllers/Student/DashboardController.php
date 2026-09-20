<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $enrollments = $user->courseEnrollments()->with('course')->get();

        $stats = [
            'enrolled_courses' => $enrollments->count(),
            'completed_courses' => $enrollments->where('status', 'completed')->count(),
            'completed_materials' => $user->materialProgress()->where('status', 'completed')->count(),
            'quiz_attempts' => \App\Models\QuizAttempt::where('student_id', $user->id)->count(),
            'average_score' => \App\Models\QuizAttempt::where('student_id', $user->id)->where('status', 'submitted')->avg('percentage') ?? 0,
        ];

        return view('student.dashboard', compact('user', 'stats', 'enrollments'));
    }
}
