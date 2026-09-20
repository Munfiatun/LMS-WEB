<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\LearningMaterial;
use App\Models\MaterialDocument;
use App\Services\Document\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaterialDocumentController extends Controller
{
    public function store(UploadDocumentRequest $request, LearningMaterial $material, DocumentService $service): RedirectResponse
    {
        $file = $request->file('document');

        $service->storeMaterialDocument($material, $file, $request->user());

        return back()->with('success', 'Dokumen PDF/Word berhasil diunggah ke private storage dan siap dianalisis AI.');
    }

    public function destroy(MaterialDocument $document, DocumentService $service): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $service->deleteMaterialDocument($document);

        return back()->with('success', 'Dokumen berhasil dihapus dari storage privat.');
    }

    public function download(MaterialDocument $document, DocumentService $service): BinaryFileResponse
    {
        Gate::authorize('view', $document);

        return $service->getDownloadResponse($document);
    }
}
