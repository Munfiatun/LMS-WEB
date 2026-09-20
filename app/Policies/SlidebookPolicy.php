<?php

namespace App\Policies;

use App\Models\LearningMaterial;
use App\Models\Slidebook;
use App\Models\User;

class SlidebookPolicy
{
    /**
     * Determine whether the user can view the slidebook.
     */
    public function view(?User $user, Slidebook $slidebook): bool
    {
        // If published and the course is published, any authenticated user can view
        if ($slidebook->isPublished() && $slidebook->material->section->course->isPublished()) {
            return $user !== null;
        }

        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->id === $slidebook->material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can generate slidebook for a material.
     */
    public function generate(User $user, LearningMaterial $material): bool
    {
        return $user->isAdmin() || $user->id === $material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can update the slidebook.
     */
    public function update(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || $user->id === $slidebook->material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can approve the slidebook.
     */
    public function approve(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || $user->id === $slidebook->material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can publish the slidebook.
     */
    public function publish(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || $user->id === $slidebook->material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can delete the slidebook.
     */
    public function delete(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || $user->id === $slidebook->material->section->course->instructor_id;
    }
}
