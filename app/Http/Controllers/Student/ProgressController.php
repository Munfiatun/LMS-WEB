<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProgressController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    public function complete(Request $request, LearningMaterial $material): RedirectResponse
    {
        $material->load('section.course');
        Gate::authorize('view', $material);
        $material->section->course->enrollments()->where('student_id', $request->user()->id)->firstOrFail();

        $this->enrollmentService->markMaterialCompleted($material, auth()->user());

        return back()->with('success', 'Materi ditandai selesai.');
    }
}
