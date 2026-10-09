<?php

namespace App\Policies;

use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\User;

class LearningMaterialPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, LearningMaterial $learningMaterial): bool
    {
        if (! $user || ! $user->is_active) {
            return false;
        }
        $course = $learningMaterial->section?->course;
        if ($user->isAdmin() || ($user->isInstructor() && $user->id === $course?->instructor_id)) {
            return true;
        }

        return $user->isStudent() && $course?->isPublished()
            && $learningMaterial->status === LearningMaterial::STATUS_PUBLISHED
            && $learningMaterial->section->status === 'active'
            && $course->enrollments()->where('student_id', $user->id)->whereIn('status', ['active', 'completed'])->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CourseSection $section): bool
    {
        return $user->isAdmin() || $user->id === $section->course->instructor_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LearningMaterial $learningMaterial): bool
    {
        return $user->isAdmin() || $user->id === $learningMaterial->section->course->instructor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LearningMaterial $learningMaterial): bool
    {
        return $user->isAdmin() || $user->id === $learningMaterial->section->course->instructor_id;
    }
}
