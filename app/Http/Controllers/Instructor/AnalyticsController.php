<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function show(Course $course)
    {
        $this->authorize('view', $course);

        $enrollments = $course->enrollments()->with('student')->paginate(20);
        
        $stats = \Illuminate\Support\Facades\Cache::remember('course_stats_' . $course->id, 300, function () use ($course) {
            return [
                'totalStudents' => $course->enrollments()->count(),
                'completedStudents' => $course->enrollments()->where('status', 'completed')->count(),
                'averageProgress' => $course->enrollments()->avg('progress_percentage') ?? 0,
            ];
        });

        $totalStudents = $stats['totalStudents'];
        $completedStudents = $stats['completedStudents'];
        $averageProgress = $stats['averageProgress'];

        return view('instructor.analytics.show', compact('course', 'enrollments', 'totalStudents', 'completedStudents', 'averageProgress'));
    }
}
