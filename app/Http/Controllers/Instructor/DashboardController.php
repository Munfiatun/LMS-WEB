<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Slidebook;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $courseIds = Course::where('instructor_id', $user->id)->pluck('id');

        $stats = [
            'total_courses' => $courseIds->count(),
            'total_materials' => \App\Models\LearningMaterial::whereHas('section', fn ($query) => $query->whereIn('course_id', $courseIds))->count(),
            'total_slidebooks' => Slidebook::whereHas('material.section', fn ($query) => $query->whereIn('course_id', $courseIds))->count(),
            'total_question_banks' => QuestionBank::where('instructor_id', $user->id)->count(),
            'total_quizzes' => Quiz::whereIn('course_id', $courseIds)->count(),
            'total_students' => \App\Models\CourseEnrollment::whereIn('course_id', $courseIds)
                ->whereIn('status', ['active', 'completed'])
                ->distinct('student_id')
                ->count('student_id'),
        ];

        return view('instructor.dashboard', compact('user', 'stats'));
    }
}
