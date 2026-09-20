<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;

class CourseSectionPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CourseSection $courseSection): bool
    {
        return $user->isAdmin() || $user->id === $courseSection->course->instructor_id || $courseSection->course->isPublished();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->id === $course->instructor_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CourseSection $courseSection): bool
    {
        return $user->isAdmin() || $user->id === $courseSection->course->instructor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CourseSection $courseSection): bool
    {
        return $user->isAdmin() || $user->id === $courseSection->course->instructor_id;
    }
}
