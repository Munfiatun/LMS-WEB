<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quiz $quiz): bool
    {
        if ($user->isAdmin() || ($user->isInstructor() && $user->id === $quiz->course?->instructor_id)) {
            return true;
        }

        if ($user->isStudent()) {
            if (! $user->is_active || ! $quiz->course?->isPublished() || ! $quiz->newQuery()->available()->whereKey($quiz->id)->exists()) {
                return false;
            }

            return $quiz->course->enrollments()->where('student_id', $user->id)->whereIn('status', ['active', 'completed'])->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin() || $user->id === $quiz->course->instructor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->isAdmin() || $user->id === $quiz->course->instructor_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Quiz $quiz): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return false;
    }
}
