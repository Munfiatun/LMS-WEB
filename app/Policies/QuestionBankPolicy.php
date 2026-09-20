<?php

namespace App\Policies;

use App\Models\QuestionBank;
use App\Models\User;

class QuestionBankPolicy
{
    /**
     * Determine whether the user can view any question banks.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can view the question bank.
     */
    public function view(User $user, QuestionBank $questionBank): bool
    {
        return $user->isAdmin() || $user->id === $questionBank->instructor_id;
    }

    /**
     * Determine whether the user can create question banks.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can update the question bank.
     */
    public function update(User $user, QuestionBank $questionBank): bool
    {
        return $user->isAdmin() || $user->id === $questionBank->instructor_id;
    }

    /**
     * Determine whether the user can delete the question bank.
     */
    public function delete(User $user, QuestionBank $questionBank): bool
    {
        return $user->isAdmin() || $user->id === $questionBank->instructor_id;
    }

    /**
     * Determine whether the user can extract questions for the bank.
     */
    public function extract(User $user, QuestionBank $questionBank): bool
    {
        return $user->isAdmin() || $user->id === $questionBank->instructor_id;
    }
}
