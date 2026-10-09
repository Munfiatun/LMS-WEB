<?php

namespace App\Policies;

use App\Models\LearningMaterial;
use App\Models\MaterialDocument;
use App\Models\User;

class MaterialDocumentPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MaterialDocument $materialDocument): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $material = $materialDocument->material;
        $course = $material?->section?->course;
        if (! $course) {
            return false;
        }

        if ($user->isInstructor()) {
            return $user->id === $course->instructor_id;
        }

        return $user->isStudent()
            && $material->section->status === 'active'
            && $course->isPublished()
            && $material->status === LearningMaterial::STATUS_PUBLISHED
            && $course->enrollments()->where('student_id', $user->id)->where('status', 'active')->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, LearningMaterial $material): bool
    {
        return $user->isAdmin() || $user->id === $material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaterialDocument $materialDocument): bool
    {
        return $user->isAdmin() || $user->id === $materialDocument->material->section->course->instructor_id;
    }
}
