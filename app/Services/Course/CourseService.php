<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CourseService
{
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

        $course->update($data);

        return $course;
    }

    public function publishCourse(Course $course): bool
    {
        return $course->update([
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function archiveCourse(Course $course): bool
    {
        return $course->update([
            'status' => Course::STATUS_ARCHIVED,
        ]);
    }

    public function deleteCourse(Course $course): bool
    {
        return (bool) $course->delete();
    }
}
