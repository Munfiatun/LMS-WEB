<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttemptQuestion extends Model
{
    use HasFactory;

    protected $table = 'quiz_attempt_questions';

    protected $fillable = [
        'attempt_id',
        'question_id',
        'order',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function attemptOptions(): HasMany
    {
        return $this->hasMany(QuizAttemptOption::class, 'attempt_question_id')->orderBy('order');
    }
}
