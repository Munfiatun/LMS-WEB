<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class MaterialService
{
    /** @param array<string, mixed> $data */
    public function createMaterial(CourseSection $section, array $data): LearningMaterial
    {
        return $section->materials()->create([
            ...Arr::only($data, ['title', 'description', 'content', 'duration_minutes', 'order']),
            'order' => $data['order'] ?? (($section->materials()->max('order') ?? 0) + 1),
            'status' => LearningMaterial::STATUS_DRAFT,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function updateMaterial(LearningMaterial $material, array $data): void
    {
        $this->assertEditable($material);
        $material->update(Arr::only($data, ['title', 'description', 'content', 'duration_minutes', 'order']));
    }

    public function assertEditable(LearningMaterial $material): void
    {
        if (! in_array($material->status, ['draft', 'review'], true)) {
            throw ValidationException::withMessages(['material' => 'Kembalikan materi ke draft melalui action khusus sebelum mengubah konten atau dokumen.']);
        }
    }

    /** @return list<string> */
    /**
     * @return array<int, array{key: string, label: string, passed: bool}>
     */
    public function getPublicationReadiness(LearningMaterial $material): array
    {
        $material->loadMissing(['section.course.instructor.role', 'documents.extraction']);
        $course = $material->section?->course;

        $courseValid = $course && $course->status !== Course::STATUS_ARCHIVED
            && $material->section->status === 'active'
            && $course->instructor?->is_active
            && $course->instructor->isInstructor();

        $statusValid = trim((string) $material->title) !== '' && ! in_array($material->status, ['processing', 'archived'], true);

        $docsValid = true;
        foreach ($material->documents as $document) {
            if ($document->extraction?->status !== 'completed' || trim((string) $document->extraction->content) === '') {
                $docsValid = false;
                break;
            }
        }

        $contentValid = trim(strip_tags((string) $material->content)) !== ''
            || $material->documents->isNotEmpty()
            || $material->publishedSlidebook()->exists();

        return [
            ['key' => 'course_active', 'label' => 'Kursus dan instruktur aktif', 'passed' => (bool) $courseValid],
            ['key' => 'status_ready', 'label' => 'Judul dan status siap', 'passed' => (bool) $statusValid],
            ['key' => 'docs_processed', 'label' => 'Dokumen selesai diproses (jika ada)', 'passed' => $docsValid],
            ['key' => 'content_exists', 'label' => 'Memiliki konten, dokumen, atau Slidebook', 'passed' => (bool) $contentValid],
        ];
    }

    public function publicationErrors(LearningMaterial $material): array
    {
        $readiness = $this->getPublicationReadiness($material);
        $errors = [];
        foreach ($readiness as $check) {
            if (! $check['passed']) {
                $errors[] = $check['label'].' belum terpenuhi.';
            }
        }

        return $errors;
    }

    public function publishMaterial(LearningMaterial $material): void
    {
        if ($errors = $this->publicationErrors($material)) {
            throw ValidationException::withMessages(['material' => $errors]);
        }
        $material->update(['status' => LearningMaterial::STATUS_PUBLISHED, 'published_at' => $material->published_at ?? now()]);
    }

    public function unpublishMaterial(LearningMaterial $material): void
    {
        if ($material->status !== LearningMaterial::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['material' => 'Hanya materi published yang dapat dikembalikan ke draft.']);
        }
        $material->update(['status' => LearningMaterial::STATUS_DRAFT, 'published_at' => null]);
    }
}
