<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractQuestionsAIJob;
use App\Models\QuestionBank;
use App\Models\QuestionDocument;
use App\Services\Document\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AIQuestionExtractController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * Upload a document for AI question extraction.
     */
    public function uploadDocument(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('update', $questionBank);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,docx,doc', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('document');

        $this->documentService->storeQuestionDocument($questionBank, $file, $request->user());

        return back()->with('success', 'Dokumen berhasil diunggah. Silakan klik "Ekstrak dengan AI" untuk mulai memproses naskah soal.');
    }

    /**
     * Dispatch the AI question extraction job.
     */
    public function extract(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('update', $questionBank);

        $request->validate([
            'document_id' => ['required', 'exists:question_documents,id'],
        ]);

        $document = QuestionDocument::where('question_bank_id', $questionBank->id)
            ->findOrFail($request->input('document_id'));

        try {
            $documentText = $this->documentService->extractTextFromQuestionDocument($document);

            // Dispatch the job
            ExtractQuestionsAIJob::dispatch($questionBank, $documentText);

            return redirect()
                ->route('instructor.question-banks.review', $questionBank)
                ->with('info', 'Proses ekstraksi soal dengan AI sedang berjalan di latar belakang. Halaman ini akan memuat ulang secara otomatis, atau Anda dapat me-refresh secara manual.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengekstrak teks dari dokumen: '.$e->getMessage());
        }
    }

    /**
     * Show the review page for AI-extracted questions that need verification.
     */
    public function review(QuestionBank $questionBank): View
    {
        Gate::authorize('view', $questionBank);

        $questionBank->load([
            'questions' => function ($query) {
                $query->where('needs_review', true)->with('options');
            },
        ]);

        return view('instructor.question_banks.review', [
            'questionBank' => $questionBank,
            'questions' => $questionBank->questions,
        ]);
    }
}
