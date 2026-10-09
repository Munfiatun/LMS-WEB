<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\LearningMaterial;
use App\Models\Slide;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\Document\DocumentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AISlidebookService
{
    public function __construct(
        protected AIContentService $aiContentService
    ) {}

    /**
     * Generate structured slidebook from learning material's extracted documents.
     */
    public function generateSlidebookForMaterial(LearningMaterial $material, User $creator, bool $forceRegenerate = false): Slidebook
    {
        $timeout = max(60, (int) config('ai.providers.'.config('ai.provider').'.timeout', 60));
        $lock = Cache::lock('slidebook-generation:'.$material->id, $timeout + 120);
        if (! $lock->get()) {
            throw ValidationException::withMessages(['slidebook' => 'Generation masih berjalan. Silakan tunggu.']);
        }
        try {
            $material->refresh();
            if (! $material->section?->course || $material->section->course->status === 'archived'
                || $material->status === 'processing') {
                throw ValidationException::withMessages(['slidebook' => 'Materi atau kursus tidak tersedia untuk generation.']);
            }
            $latest = $material->slidebooks()->latest('version')->first();
            if (! $forceRegenerate && $latest) {
                return $latest;
            }

            return $this->generate($material, $creator, $forceRegenerate);
        } finally {
            $lock->release();
        }
    }

    private function generate(LearningMaterial $material, User $creator, bool $forceRegenerate): Slidebook
    {
        // Find extracted documents for this material
        $documents = $material->documents()->with('extraction')->get();

        $extractedTexts = [];
        foreach ($documents as $doc) {
            if ($doc->extraction && $doc->extraction->status === 'completed' && ! empty($doc->extraction->content)) {
                $extractedTexts[] = "=== DOKUMEN: {$doc->original_name} ===\n".$doc->extraction->content;
            }
        }

        if (empty($extractedTexts)) {
            // If documents exist without extraction, try parsing now
            $documentService = app(DocumentService::class);
            foreach ($documents as $doc) {
                $extraction = $documentService->extractDocumentContent($doc);
                if ($extraction->status === 'completed' && ! empty($extraction->content)) {
                    $extractedTexts[] = "=== DOKUMEN: {$doc->original_name} ===\n".$extraction->content;
                }
            }
        }

        if (empty($extractedTexts)) {
            // Fallback: Use material content or description if present
            if (! empty($material->content)) {
                $extractedTexts[] = "=== KONTEN MATERI: {$material->title} ===\n".$material->content;
            } elseif (! empty($material->description)) {
                $extractedTexts[] = "=== DESKRIPSI MATERI: {$material->title} ===\n".$material->description;
            } else {
                throw new RuntimeException('Belum ada dokumen yang berhasil diekstrak atau konten materi untuk diproses AI.');
            }
        }

        $sourceContent = implode("\n\n", $extractedTexts);

        $systemPrompt = <<<'PROMPT'
Anda adalah asisten kurikulum pembelajaran profesional. Tugas Anda adalah menganalisis dokumen ajar dan menyusun draft "Slidebook" (rangkaian slide presentasi interaktif) dalam Bahasa Indonesia.

Prinsip Wajib (Source Fidelity):
1. Utamakan kebenaran fakta dari dokumen sumber. Dilarang mengarang informasi yang bertentangan dengan materi.
2. Jika sebuah poin penting disimpulkan secara umum (bukan kutipan langsung), tandai `needs_review: true`.
3. Setiap slide harus memiliki:
   - title: Judul ringkas, menarik, dan informatif (maks 80 karakter).
   - subtitle: Sub-judul atau fokus bahasan.
   - content: Poin-poin materi dalam format teks bersih atau daftar bernomor.
   - summary: Rangkuman intisari slide (1-2 kalimat).
   - order: Urutan integer (mulai dari 1).
   - source_reference: Objek yang menerangkan rujukan asal di dokumen (misal: {"paragraph": 1} atau {"topic": "Pengantar"}).
   - needs_review: Boolean (true jika memerlukan verifikasi guru).
4. Struktur Slidebook:
   - Slide 1: Cover (Judul materi, ringkasan pengantar)
   - Slide 2: Tujuan Pembelajaran (Capaian belajar)
   - Slide 3..N: Topik inti yang terperinci dan runtut
   - Slide Terakhir: Rangkuman & Intisari
PROMPT;

        $schemaDefinition = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'subtitle' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'slides' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'order' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'subtitle' => ['type' => 'string'],
                            'content' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'source_reference' => ['type' => 'object'],
                            'needs_review' => ['type' => 'boolean'],
                        ],
                        'required' => ['order', 'title', 'content'],
                    ],
                ],
            ],
            'required' => ['title', 'slides'],
        ];

        // Process with AI
        $aiOutput = $this->aiContentService->process(
            processType: AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            sourceModel: $material,
            systemPrompt: $systemPrompt,
            userContent: $sourceContent,
            schemaDefinition: $schemaDefinition,
            forceRegenerate: $forceRegenerate
        );

        $parsed = $aiOutput['parsed_data'];
        $validator = Validator::make($parsed, [
            'title' => ['required', 'string', 'max:255'],
            'slides' => ['required', 'array', 'min:1'],
            'slides.*.title' => ['required', 'string', 'max:255'],
            'slides.*.content' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            $aiOutput['log']->update(['status' => 'failed', 'error_message' => 'Output Slidebook AI tidak valid.']);
            throw ValidationException::withMessages(['slidebook' => 'Output Slidebook AI tidak valid. Silakan coba kembali.']);
        }

        return DB::transaction(function () use ($material, $creator, $parsed): Slidebook {
            LearningMaterial::whereKey($material->id)->lockForUpdate()->firstOrFail();

            // Archive any existing draft/review revisions to prevent orphaned duplicates.
            // The published version is intentionally left untouched.
            $material->slidebooks()
                ->whereIn('status', [Slidebook::STATUS_DRAFT, Slidebook::STATUS_REVIEW])
                ->update(['status' => Slidebook::STATUS_ARCHIVED]);

            // Determine version
            $latestSlidebook = Slidebook::where('material_id', $material->id)->latest('version')->first();
            $nextVersion = $latestSlidebook ? ($latestSlidebook->version + 1) : 1;

            // Create Slidebook in 'review' status (Human-in-the-Loop)
            $slidebook = Slidebook::create([
                'material_id' => $material->id,
                'title' => $parsed['title'] ?? $material->title,
                'subtitle' => $parsed['subtitle'] ?? 'Slidebook Interaktif Pembelajaran',
                'description' => $parsed['description'] ?? $material->description,
                'status' => Slidebook::STATUS_REVIEW,
                'version' => $nextVersion,
                'created_by' => $creator->id,
            ]);

            $slidesData = $parsed['slides'] ?? [];
            foreach ($slidesData as $index => $slideItem) {
                Slide::create([
                    'slidebook_id' => $slidebook->id,
                    'title' => $slideItem['title'] ?? ('Slide '.($index + 1)),
                    'subtitle' => $slideItem['subtitle'] ?? null,
                    'content' => $slideItem['content'] ?? '',
                    'summary' => $slideItem['summary'] ?? null,
                    'order' => (int) ($slideItem['order'] ?? ($index + 1)),
                    'source_reference' => $slideItem['source_reference'] ?? null,
                    'needs_review' => (bool) ($slideItem['needs_review'] ?? false),
                    'status' => 'active',
                ]);
            }

            // Update learning material status if needed
            if ($material->status === LearningMaterial::STATUS_DRAFT) {
                $material->update(['status' => LearningMaterial::STATUS_REVIEW]);
            }

            return $slidebook;
        });
    }
}
