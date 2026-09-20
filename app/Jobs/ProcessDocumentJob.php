<?php

namespace App\Jobs;

use App\Models\MaterialDocument;
use App\Services\Document\DocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(public MaterialDocument $document) {}

    public function handle(DocumentService $documentService): void
    {
        $documentService->extractDocumentContent($this->document);
    }

    public function failed(?Throwable $exception): void
    {
        // Extraction status is automatically updated to failed in DocumentService
    }
}
