<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService)
    {
    }

    public function complete(Request $request, LearningMaterial $material)
    {
        $this->enrollmentService->markMaterialCompleted($material, auth()->user());
        
        return back()->with('success', 'Materi ditandai selesai.');
    }
}
