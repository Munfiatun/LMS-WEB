<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'total_courses' => 0,
            'total_materials' => 0,
            'total_slidebooks' => 0,
            'total_question_banks' => 0,
            'total_quizzes' => 0,
            'total_students' => 0,
        ];

        return view('instructor.dashboard', compact('user', 'stats'));
    }
}
