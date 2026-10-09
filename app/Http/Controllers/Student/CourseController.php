<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $enrolledCourseIds = CourseEnrollment::where('student_id', $request->user()->id)
            ->whereIn('status', ['active', 'completed'])->pluck('course_id');

        $courses = Course::published()->whereIn('id', $enrolledCourseIds)
            ->with([
                'category',
                'instructor',
                'enrollments' => function ($query) use ($request) {
                    $query->where('student_id', $request->user()->id);
                },
            ])
            ->withCount(['sections', 'materials', 'quizzes'])
            ->latest()
            ->paginate(10);

        return view('student.courses.index', compact('courses'));
    }

    public function explore(Request $request): View
    {
        $query = Course::published()
            ->with(['category', 'instructor'])
            ->withCount(['sections', 'materials', 'quizzes']);

        if ($request->has('q') && $request->q !== '') {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%'.$searchTerm.'%')
                    ->orWhereHas('category', function ($catQ) use ($searchTerm) {
                        $catQ->where('name', 'like', '%'.$searchTerm.'%');
                    });
            });
        }

        $courses = $query->latest()->paginate(10)->withQueryString();

        $enrolledCourseIds = CourseEnrollment::where('student_id', $request->user()->id)
            ->pluck('course_id')
            ->toArray();

        return view('student.courses.explore', compact('courses', 'enrolledCourseIds'));
    }
}
