<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use App\Models\Slidebook;
use App\Services\SlidebookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SlideController extends Controller
{
    public function __construct(private SlidebookService $service) {}

    /**
     * Add a new slide to the slidebook.
     */
    public function store(Request $request, Slidebook $slidebook): RedirectResponse
    {
        Gate::authorize('create', [Slide::class, $slidebook]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
            'layout' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->addSlide($slidebook, $validated);

        return back()->with('success', 'Lembar slide baru berhasil ditambahkan.');
    }

    /**
     * Update an existing slide.
     */
    public function update(Request $request, Slide $slide): RedirectResponse
    {
        Gate::authorize('update', $slide);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
            'layout' => ['nullable', 'string', 'max:255'],
            'needs_review' => ['nullable', 'boolean'],
        ]);

        $this->service->updateSlide($slide, $validated);

        return back()->with('success', "Slide #{$slide->order} berhasil diperbarui.");
    }

    /**
     * Delete a slide from the slidebook.
     */
    public function destroy(Request $request, Slide $slide): RedirectResponse
    {
        Gate::authorize('delete', $slide);

        $this->service->deleteSlide($slide);

        return back()->with('success', 'Slide berhasil dihapus.');
    }

    /**
     * Reorder slides via JSON / Ajax or Form.
     */
    public function reorder(Request $request, Slidebook $slidebook): JsonResponse|RedirectResponse
    {
        Gate::authorize('reorder', [Slide::class, $slidebook]);

        $validated = $request->validate([
            'slide_ids' => ['required', 'array'],
            'slide_ids.*' => ['integer', 'distinct', 'exists:slides,id'],
        ]);

        $this->service->reorder($slidebook, $validated['slide_ids']);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Urutan slide berhasil disimpan.']);
        }

        return back()->with('success', 'Urutan slide berhasil diperbarui.');
    }
}
