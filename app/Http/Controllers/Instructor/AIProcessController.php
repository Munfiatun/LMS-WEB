<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Slidebook;
use App\Services\AI\AISlidebookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AIProcessController extends Controller
{
    public function __construct(
        protected AISlidebookService $slidebookService
    ) {}

    /**
     * Trigger AI slidebook generation for a given learning material.
     */
    public function generateSlidebook(Request $request, LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('generate', [Slidebook::class, $material]);

        try {
            $slidebook = $this->slidebookService->generateSlidebookForMaterial(
                material: $material,
                creator: $request->user(),
                forceRegenerate: $request->boolean('regenerate')
            );

            return redirect()
                ->route('instructor.materials.slidebook.review', $material)
                ->with('success', 'Slidebook berhasil dibuat oleh AI dan siap untuk ditinjau!');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('AI slidebook processing failed', ['exception_type' => $e::class, 'material_id' => $material->id]);

            return back()->with('error', 'Proses AI gagal. Silakan coba kembali.');
        }
    }
}
