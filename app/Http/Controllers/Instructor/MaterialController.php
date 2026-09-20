<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaterialRequest;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function create(CourseSection $section): View
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);

        return view('instructor.materials.create', compact('section'));
    }

    public function store(StoreMaterialRequest $request, CourseSection $section): RedirectResponse
    {
        $maxOrder = $section->materials()->max('order') ?? 0;

        $material = $section->materials()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'content' => $request->validated('content'),
            'duration_minutes' => $request->validated('duration_minutes') ?? 10,
            'order' => $request->validated('order') ?? ($maxOrder + 1),
            'status' => $request->validated('status') ?? LearningMaterial::STATUS_DRAFT,
        ]);

        return redirect()->route('instructor.materials.edit', $material)->with('success', 'Materi berhasil dibuat. Anda dapat mengunggah berkas PDF/Word pendukung.');
    }

    public function edit(LearningMaterial $material): View
    {
        Gate::authorize('update', $material);

        $material->load([
            'section.course',
            'documents',
        ]);

        return view('instructor.materials.edit', compact('material'));
    }

    public function update(StoreMaterialRequest $request, LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('update', $material);

        $material->update($request->validated());

        return back()->with('success', 'Konten materi berhasil diperbarui.');
    }

    public function destroy(LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('delete', $material);

        $course = $material->section->course;
        $material->delete();

        return redirect()->route('instructor.courses.edit', $course)->with('success', 'Materi berhasil dihapus.');
    }
}
