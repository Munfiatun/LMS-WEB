<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningMaterial;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function show(Course $course, LearningMaterial $material)
    {
        // Pastikan student terdaftar
        $enrollment = $course->enrollments()->where('student_id', auth()->id())->firstOrFail();

        // Update last accessed
        $enrollment->last_accessed_material_id = $material->id;
        $enrollment->last_accessed_quiz_id = null;
        $enrollment->save();

        $isCompleted = auth()->user()->materialProgress()->where('learning_material_id', $material->id)->where('status', 'completed')->exists();

        return view('student.materials.show', compact('course', 'material', 'isCompleted'));
    }
}
