<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'status',
        'progress_percentage',
        'completed_at',
        'last_accessed_material_id',
        'last_accessed_quiz_id',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function lastAccessedMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'last_accessed_material_id');
    }

    public function lastAccessedQuiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'last_accessed_quiz_id');
    }
}
