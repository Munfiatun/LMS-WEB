<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnrollmentController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'enrollment_code' => ['required', 'string'],
        ]);

        $token = strtoupper(trim($request->input('enrollment_code')));

        if ($course->enrollment_code !== $token) {
            return back()->with('error', 'Token kelas tidak valid.');
        }

        $enrollment = $course->enrollments()->where('student_id', auth()->id())->first();
        if ($enrollment) {
            return back()->with('error', 'Anda sudah terdaftar pada kelas ini.');
        }

        $this->enrollmentService->enrollStudent($course, auth()->user());

        return redirect()->route('student.courses.index')->with('success', 'Berhasil bergabung ke kelas.');
    }

    public function continue(Course $course)
    {
        Gate::authorize('view', $course);
        $enrollment = $course->enrollments()->where('student_id', auth()->id())->first();

        if (! $enrollment) {
            return redirect()->route('courses.show', $course->slug);
        }

        if ($enrollment->last_accessed_material_id) {
            return redirect()->route('student.materials.show', [
                'course' => $course,
                'material' => $enrollment->last_accessed_material_id,
            ]);
        }

        if ($enrollment->last_accessed_quiz_id) {
            return redirect()->route('student.quizzes.show', $enrollment->last_accessed_quiz_id);
        }

        // Default to first material if exists
        $firstMaterial = $course->materials()->where('learning_materials.status', 'published')->orderBy('course_sections.order')->orderBy('learning_materials.order')->first();
        if ($firstMaterial) {
            return redirect()->route('student.materials.show', [
                'course' => $course,
                'material' => $firstMaterial,
            ]);
        }

        return redirect()->route('courses.show', $course->slug);
    }
}
