<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use App\Models\Slidebook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SlideController extends Controller
{
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
        ]);

        $maxOrder = (int) $slidebook->slides()->max('order');

        $slidebook->slides()->create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'content' => $validated['content'],
            'summary' => $validated['summary'] ?? null,
            'order' => $maxOrder + 1,
            'source_reference' => ['manual' => true],
            'needs_review' => false,
            'status' => 'active',
        ]);

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
            'needs_review' => ['nullable', 'boolean'],
        ]);

        $slide->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'content' => $validated['content'],
            'summary' => $validated['summary'] ?? null,
            'needs_review' => $request->has('needs_review') ? $request->boolean('needs_review') : $slide->needs_review,
        ]);

        return back()->with('success', "Slide #{$slide->order} berhasil diperbarui.");
    }

    /**
     * Delete a slide from the slidebook.
     */
    public function destroy(Request $request, Slide $slide): RedirectResponse
    {
        Gate::authorize('delete', $slide);

        $slidebook = $slide->slidebook;
        $slideOrder = $slide->order;
        $slide->delete();

        // Re-index remaining slides order
        $slidebook->slides()->where('order', '>', $slideOrder)->decrement('order');

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
            'slide_ids.*' => ['integer', 'exists:slides,id'],
        ]);

        foreach ($validated['slide_ids'] as $index => $slideId) {
            $slidebook->slides()->where('id', $slideId)->update(['order' => $index + 1]);
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Urutan slide berhasil disimpan.']);
        }

        return back()->with('success', 'Urutan slide berhasil diperbarui.');
    }
}
