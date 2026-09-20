<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService)
    {
    }

    public function store(Course $course)
    {
        \Log::info('Store hit');
        $this->enrollmentService->enrollStudent($course, auth()->user());
        return redirect()->route('courses.show', $course->slug)->with('success', 'Berhasil mendaftar ke kelas.');
    }

    public function continue(Course $course)
    {
        $enrollment = $course->enrollments()->where('student_id', auth()->id())->first();
        
        if (!$enrollment) {
            return redirect()->route('courses.show', $course->slug);
        }

        if ($enrollment->last_accessed_material_id) {
            return redirect()->route('student.materials.show', [
                'course' => $course,
                'material' => $enrollment->last_accessed_material_id
            ]);
        }

        if ($enrollment->last_accessed_quiz_id) {
            return redirect()->route('student.quizzes.show', $enrollment->last_accessed_quiz_id);
        }

        // Default to first material if exists
        $firstMaterial = $course->sections()->first()?->materials()->first();
        if ($firstMaterial) {
            return redirect()->route('student.materials.show', [
                'course' => $course,
                'material' => $firstMaterial
            ]);
        }

        return redirect()->route('courses.show', $course->slug);
    }
}
