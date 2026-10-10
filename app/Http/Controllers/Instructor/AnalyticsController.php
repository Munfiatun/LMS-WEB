<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseAnalyticsService;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private CourseAnalyticsService $analytics) {}

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $analytics = $this->analytics->summarize($course);
        $enrollments = $course->enrollments()
            ->with('student')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('instructor.analytics.show', [
            'course' => $course,
            'enrollments' => $enrollments,
            ...$analytics,
        ]);
    }
}
