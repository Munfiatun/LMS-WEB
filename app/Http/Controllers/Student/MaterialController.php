<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningMaterial;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function show(Course $course, LearningMaterial $material): View
    {
        $material->load('section.course');
        abort_unless($material->section->course_id === $course->id, 404);
        Gate::authorize('view', $course);
        Gate::authorize('view', $material);

        // Pastikan student terdaftar
        $enrollment = $course->enrollments()->where('student_id', auth()->id())->firstOrFail();

        // Eager load relasi yang dibutuhkan view untuk mencegah LazyLoadingViolationException
        $course->load(['sections.materials' => fn ($query) => $query->published(), 'quizzes' => fn ($query) => $query->where('status', 'published')]);
        $material->load(['slidebook', 'documents']);

        // Update last accessed
        $enrollment->last_accessed_material_id = $material->id;
        $enrollment->last_accessed_quiz_id = null;
        $enrollment->save();

        // Ambil semua progress material yang completed untuk user ini agar tidak N+1 di Blade
        $completedMaterialIds = auth()->user()
            ->materialProgress()
            ->where('status', 'completed')
            ->whereHas('learningMaterial.section', fn ($query) => $query->where('course_id', $course->id))
            ->pluck('learning_material_id')
            ->toArray();

        $isCompleted = in_array($material->id, $completedMaterialIds);

        return view('student.materials.show', compact('course', 'material', 'isCompleted', 'completedMaterialIds'));
    }
}
