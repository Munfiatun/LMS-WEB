<?php

namespace App\Policies;

use App\Models\Slide;
use App\Models\Slidebook;
use App\Models\User;

class SlidePolicy
{
    /**
     * Determine whether the user can create slides in a slidebook.
     */
    public function create(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || $user->id === $slidebook->material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can update the slide.
     */
    public function update(User $user, Slide $slide): bool
    {
        return $user->isAdmin() || $user->id === $slide->slidebook->material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can delete the slide.
     */
    public function delete(User $user, Slide $slide): bool
    {
        return $user->isAdmin() || $user->id === $slide->slidebook->material->section->course->instructor_id;
    }

    /**
     * Determine whether the user can reorder slides in a slidebook.
     */
    public function reorder(User $user, Slidebook $slidebook): bool
    {
        return $user->isAdmin() || $user->id === $slidebook->material->section->course->instructor_id;
    }
}
