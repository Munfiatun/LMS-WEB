<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Slidebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SlidebookViewerController extends Controller
{
    /**
     * Display the presentation viewer for an approved & published slidebook.
     */
    public function show(Request $request, Slidebook $slidebook): View
    {
        Gate::authorize('view', $slidebook);

        $slidebook->load([
            'slides' => fn ($q) => $q->orderBy('order'),
            'material.section.course.instructor',
        ]);

        return view('student.slidebooks.show', [
            'slidebook' => $slidebook,
            'material' => $slidebook->material,
            'course' => $slidebook->material->section->course,
            'slides' => $slidebook->slides,
        ]);
    }
}
