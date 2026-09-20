<?php

namespace App\Services\Document;

use App\Models\DocumentExtraction;
use App\Models\LearningMaterial;
use App\Models\MaterialDocument;
use App\Models\QuestionBank;
use App\Models\QuestionDocument;
use App\Models\User;
use App\Services\Document\Contracts\DocumentParserInterface;
use App\Services\Document\Parsers\DocxDocumentParser;
use App\Services\Document\Parsers\PdfDocumentParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DocumentService
{
    protected string $disk = 'private';

    public function storeMaterialDocument(LearningMaterial $material, UploadedFile $file, User $uploader): MaterialDocument
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $size = $file->getSize();

        $safeOriginalName = Str::slug($originalName).'.'.$extension;
        $storedName = 'material_'.$material->id.'_'.(string) Str::uuid().'.'.$extension;
        $relativePath = 'materials/'.$material->id.'/'.$storedName;

        // Save to private storage
        Storage::disk($this->disk)->putFileAs('materials/'.$material->id, $file, $storedName);

        $document = MaterialDocument::create([
            'material_id' => $material->id,
            'original_name' => $safeOriginalName,
            'stored_name' => $storedName,
            'disk' => $this->disk,
            'path' => $relativePath,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'uploaded_by' => $uploader->id,
        ]);

        // Automatically extract content for document
        $this->extractDocumentContent($document);

        return $document;
    }

    public function storeQuestionDocument(QuestionBank $bank, UploadedFile $file, User $uploader): QuestionDocument
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $size = $file->getSize();

        $safeOriginalName = Str::slug($originalName).'.'.$extension;
        $storedName = 'qbank_'.$bank->id.'_'.(string) Str::uuid().'.'.$extension;
        $relativePath = 'question_banks/'.$bank->id.'/'.$storedName;

        // Save to private storage
        Storage::disk($this->disk)->putFileAs('question_banks/'.$bank->id, $file, $storedName);

        return QuestionDocument::create([
            'question_bank_id' => $bank->id,
            'original_name' => $safeOriginalName,
            'stored_name' => $storedName,
            'disk' => $this->disk,
            'path' => $relativePath,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'uploaded_by' => $uploader->id,
        ]);
    }

    public function extractTextFromQuestionDocument(QuestionDocument $document): string
    {
        $fullPath = Storage::disk($document->disk)->path($document->path);
        $parser = $this->getParserForExtension($document->extension);
        $result = $parser->parse($fullPath);

        return $result['content'] ?? '';
    }

    public function getParserForExtension(string $extension): DocumentParserInterface
    {
        return match (strtolower($extension)) {
            'docx' => new DocxDocumentParser,
            'pdf' => new PdfDocumentParser,
            default => throw new \InvalidArgumentException("No parser supported for extension: {$extension}"),
        };
    }

    public function extractDocumentContent(MaterialDocument $document): DocumentExtraction
    {
        $fullPath = Storage::disk($document->disk)->path($document->path);

        try {
            $parser = $this->getParserForExtension($document->extension);
            $result = $parser->parse($fullPath);

            return DocumentExtraction::updateOrCreate(
                ['document_id' => $document->id],
                [
                    'version' => 1,
                    'content' => $result['content'],
                    'page_count' => $result['page_count'],
                    'word_count' => $result['word_count'],
                    'character_count' => $result['character_count'],
                    'metadata' => $result['metadata'],
                    'status' => 'completed',
                    'error_message' => null,
                ]
            );
        } catch (Throwable $e) {
            return DocumentExtraction::updateOrCreate(
                ['document_id' => $document->id],
                [
                    'version' => 1,
                    'content' => '',
                    'page_count' => null,
                    'word_count' => 0,
                    'character_count' => 0,
                    'metadata' => ['error' => $e->getMessage()],
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]
            );
        }
    }

    public function deleteMaterialDocument(MaterialDocument $document): bool
    {
        if (Storage::disk($document->disk)->exists($document->path)) {
            Storage::disk($document->disk)->delete($document->path);
        }

        return (bool) $document->delete();
    }

    public function getDownloadResponse(MaterialDocument $document): BinaryFileResponse
    {
        $fullPath = Storage::disk($document->disk)->path($document->path);

        return response()->download($fullPath, $document->original_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }
}
