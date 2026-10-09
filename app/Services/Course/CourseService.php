<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\User;
use App\Services\MaterialService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function __construct(private MaterialService $materialService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCourse(User $instructor, array $data): Course
    {
        $thumbnailPath = null;
        if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
            $thumbnailPath = $data['thumbnail']->store('thumbnails', 'public');
        }

        return Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'thumbnail' => $thumbnailPath,
            'status' => Course::STATUS_DRAFT,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCourse(Course $course, array $data): Course
    {
        if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
            if ($course->thumbnail && Storage::disk('public')->exists($course->thumbnail)) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $data['thumbnail'] = $data['thumbnail']->store('thumbnails', 'public');
        } else {
            unset($data['thumbnail']);
        }

        $course->update(Arr::only($data, ['title', 'description', 'category_id', 'thumbnail']));

        return $course;
    }

    /** @return list<string> */
    public function publicationErrors(Course $course): array
    {
        $course->loadMissing('instructor.role');
        if ($course->status !== Course::STATUS_DRAFT || trim($course->title) === ''
            || ! $course->instructor?->is_active || ! $course->instructor->isInstructor()) {
            return ['Kursus harus berstatus draft, memiliki judul, dan instruktur aktif yang valid.'];
        }
        $materials = $course->materials()->published()->with(['section.course.instructor.role', 'documents.extraction'])->get();
        if (! $materials->contains(fn ($material): bool => $this->materialService->publicationErrors($material) === [])) {
            return ['Kursus harus memiliki minimal satu materi published yang valid sebelum dipublikasikan.'];
        }

        return [];
    }

    public function publishCourse(Course $course): bool
    {
        if ($errors = $this->publicationErrors($course)) {
            throw ValidationException::withMessages(['course' => $errors]);
        }

        return $course->update([
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function archiveCourse(Course $course): bool
    {
        if (! $course->isPublished()) {
            throw ValidationException::withMessages(['course' => 'Hanya kursus published yang dapat diarsipkan.']);
        }

        return $course->update([
            'status' => Course::STATUS_ARCHIVED,
        ]);
    }

    public function deleteCourse(Course $course): bool
    {
        return (bool) $course->delete();
    }
}
