<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaterialRequest;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Services\MaterialService;
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

    public function store(StoreMaterialRequest $request, CourseSection $section, MaterialService $service): RedirectResponse
    {
        $material = $service->createMaterial($section, $request->validated());

        return redirect()->route('instructor.materials.edit', $material)->with('success', 'Materi berhasil dibuat. Anda dapat mengunggah berkas PDF/Word pendukung.');
    }

    public function edit(LearningMaterial $material, MaterialService $service): View
    {
        Gate::authorize('update', $material);

        $material->load([
            'section.course',
            'documents.extraction',
            'slidebook',
        ]);

        $publicationErrors = $service->publicationErrors($material);

        return view('instructor.materials.edit', compact('material', 'publicationErrors'));
    }

    public function update(StoreMaterialRequest $request, LearningMaterial $material, MaterialService $service): RedirectResponse
    {
        Gate::authorize('update', $material);

        $service->updateMaterial($material, $request->validated());

        return back()->with('success', 'Konten materi berhasil diperbarui.');
    }

    public function publish(LearningMaterial $material, MaterialService $service): RedirectResponse
    {
        Gate::authorize('update', $material);
        $service->publishMaterial($material);

        return back()->with('success', 'Materi berhasil dipublikasikan.');
    }

    public function unpublish(LearningMaterial $material, MaterialService $service): RedirectResponse
    {
        Gate::authorize('update', $material);
        $service->unpublishMaterial($material);

        return back()->with('success', 'Materi kembali ke draft untuk diedit dan ditinjau.');
    }

    public function destroy(LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('delete', $material);

        $course = $material->section->course;
        $material->delete();

        return redirect()->route('instructor.courses.edit', $course)->with('success', 'Materi berhasil dihapus.');
    }
}
