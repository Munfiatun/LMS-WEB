<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptOption extends Model
{
    use HasFactory;

    protected $table = 'quiz_attempt_options';

    protected $fillable = [
        'attempt_question_id',
        'option_id',
        'order',
    ];

    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(QuizAttemptQuestion::class, 'attempt_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'option_id');
    }
}
