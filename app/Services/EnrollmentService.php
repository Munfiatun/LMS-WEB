<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Daftarkan student ke sebuah course jika belum terdaftar.
     */
    public function enrollStudent(Course $course, User $student): CourseEnrollment
    {
        return CourseEnrollment::firstOrCreate(
            ['course_id' => $course->id, 'student_id' => $student->id],
            ['status' => 'active', 'progress_percentage' => 0]
        );
    }

    /**
     * Tandai sebuah material selesai dan update progress course.
     */
    public function markMaterialCompleted(LearningMaterial $material, User $student): MaterialProgress
    {
        return DB::transaction(function () use ($material, $student) {
            $progress = MaterialProgress::updateOrCreate(
                ['learning_material_id' => $material->id, 'student_id' => $student->id],
                ['status' => 'completed', 'completed_at' => now()]
            );

            $this->updateProgress($material->section->course, $student);

            // Set last accessed material
            $this->setLastAccessed($material->section->course, $student, material: $material);

            return $progress;
        });
    }

    /**
     * Hitung ulang progress percentage.
     */
    public function updateProgress(Course $course, User $student): void
    {
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $enrollment) {
            return;
        }

        // Get total materials and quizzes
        $totalMaterials = LearningMaterial::whereHas('section', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->where('status', 'published')->count();

        $totalQuizzes = $course->quizzes()->where('status', 'published')->count();
        $totalItems = $totalMaterials + $totalQuizzes;

        if ($totalItems === 0) {
            $enrollment->update(['progress_percentage' => 100, 'status' => 'completed', 'completed_at' => now()]);

            return;
        }

        // Completed materials
        $completedMaterials = MaterialProgress::where('student_id', $student->id)
            ->whereHas('learningMaterial', fn ($query) => $query->where('status', 'published'))
            ->whereHas('learningMaterial.section', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'completed')
            ->count();

        // Completed quizzes (passed)
        $completedQuizzes = $course->quizzes()->where('status', 'published')->whereHas('attempts', function ($q) use ($student) {
            $q->where('student_id', $student->id)
                ->where('status', 'submitted')
                ->whereColumn('percentage', '>=', 'quizzes.passing_score');
        })->count();

        $completedItems = $completedMaterials + $completedQuizzes;

        $percentage = ($completedItems / $totalItems) * 100;

        $enrollment->progress_percentage = min(100, $percentage);

        if ($enrollment->progress_percentage >= 100) {
            $enrollment->status = 'completed';
            $enrollment->completed_at = $enrollment->completed_at ?? now();
        } elseif ($enrollment->status === 'completed') {
            $enrollment->status = 'active';
            $enrollment->completed_at = null;
        }

        $enrollment->save();
    }

    /**
     * Set posisi terakhir belajar.
     */
    public function setLastAccessed(Course $course, User $student, ?LearningMaterial $material = null, ?Quiz $quiz = null): void
    {
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if ($enrollment) {
            if ($material) {
                $enrollment->last_accessed_material_id = $material->id;
                $enrollment->last_accessed_quiz_id = null;
            } elseif ($quiz) {
                $enrollment->last_accessed_quiz_id = $quiz->id;
                $enrollment->last_accessed_material_id = null;
            }
            $enrollment->save();
        }
    }
}
