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
        $course = $materialDocument->material->section->course;

        if ($user->isAdmin() || $user->id === $course->instructor_id) {
            return true;
        }

        // Student can view if course & material are published
        return $course->isPublished() && $materialDocument->material->status === LearningMaterial::STATUS_PUBLISHED;
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
