<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;

class QuestionPolicy
{
    /**
     * Determine whether the user can create questions in the bank.
     */
    public function create(User $user, QuestionBank $questionBank): bool
    {
        return $user->isAdmin() || $user->id === $questionBank->instructor_id;
    }

    /**
     * Determine whether the user can update the question.
     */
    public function update(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->id === $question->questionBank->instructor_id;
    }

    /**
     * Determine whether the user can delete the question.
     */
    public function delete(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->id === $question->questionBank->instructor_id;
    }

    /**
     * Determine whether the user can approve/verify the question.
     */
    public function approve(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->id === $question->questionBank->instructor_id;
    }
}
