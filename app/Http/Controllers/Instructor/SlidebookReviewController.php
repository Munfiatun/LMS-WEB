<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Slidebook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SlidebookReviewController extends Controller
{
    /**
     * Show the side-by-side Slidebook review page.
     */
    public function show(Request $request, LearningMaterial $material): View|RedirectResponse
    {
        Gate::authorize('generate', [Slidebook::class, $material]);

        $material->load([
            'section.course',
            'documents.extraction',
            'slidebooks' => fn ($q) => $q->with('slides')->latest('version'),
        ]);

        $slidebook = $material->slidebooks->first();

        if (! $slidebook) {
            return redirect()
                ->route('instructor.materials.edit', $material)
                ->with('error', 'Materi ini belum memiliki draft Slidebook. Silakan generate terlebih dahulu.');
        }

        return view('instructor.slidebooks.review', [
            'material' => $material,
            'slidebook' => $slidebook,
            'course' => $material->section->course,
        ]);
    }

    /**
     * Approve the Slidebook draft.
     */
    public function approve(Request $request, Slidebook $slidebook): RedirectResponse
    {
        Gate::authorize('approve', $slidebook);

        $slidebook->update([
            'status' => Slidebook::STATUS_DRAFT,
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Slidebook berhasil disetujui (Approved) dan tersimpan sebagai draft siap rilis.');
    }

    /**
     * Publish the Slidebook to enrolled students.
     */
    public function publish(Request $request, Slidebook $slidebook): RedirectResponse
    {
        Gate::authorize('publish', $slidebook);

        $slidebook->update([
            'status' => Slidebook::STATUS_PUBLISHED,
            'approved_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        // Publish material too if it is in review
        $slidebook->material->update([
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return back()->with('success', 'Slidebook resmi dipublikasikan! Siswa yang terdaftar kini dapat membaca materi ini.');
    }
}
