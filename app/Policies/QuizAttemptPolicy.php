<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizAttemptPolicy
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
    public function view(User $user, QuizAttempt $quizAttempt): bool
    {
        return $user->isAdmin() 
            || $user->id === $quizAttempt->quiz->course->instructor_id
            || $user->id === $quizAttempt->student_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QuizAttempt $quizAttempt): bool
    {
        return $user->id === $quizAttempt->student_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }
}
