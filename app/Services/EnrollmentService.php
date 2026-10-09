<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    /**
     * Daftarkan student ke sebuah course jika belum terdaftar.
     */
    public function enrollStudent(Course $course, User $student, ?string $code = null): CourseEnrollment
    {
        return DB::transaction(function () use ($course, $student, $code): CourseEnrollment {
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            if (! $student->isStudent() || ! $student->is_active || ! $course->isPublished()
                || ! $course->instructor?->is_active) {
                throw ValidationException::withMessages(['enrollment_code' => 'Kursus belum dipublikasikan atau tidak tersedia untuk pendaftaran.']);
            }
            if ($code !== null && (! $course->enrollment_code || ! hash_equals($course->enrollment_code, strtoupper(trim($code))))) {
                throw ValidationException::withMessages(['enrollment_code' => 'Token kelas tidak valid.']);
            }

            return CourseEnrollment::firstOrCreate(
                ['course_id' => $course->id, 'student_id' => $student->id],
                ['status' => 'active', 'progress_percentage' => 0]
            );
        });
    }

    /**
     * Tandai sebuah material selesai dan update progress course.
     */
    public function markMaterialCompleted(LearningMaterial $material, User $student): MaterialProgress
    {
        Gate::forUser($student)->authorize('view', $material);

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

        if (! $enrollment || $enrollment->status === 'dropped') {
            return;
        }

        $totalMaterials = $course->materials()->published()->count();
        $totalQuizzes = $course->quizzes()->available()->count();
        $totalItems = $totalMaterials + $totalQuizzes;

        // Completed materials
        $completedMaterials = MaterialProgress::where('student_id', $student->id)
            ->whereHas('learningMaterial', fn ($query) => $query->published())
            ->whereHas('learningMaterial.section', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'completed')
            ->count();

        // Completed quizzes (passed)
        $completedQuizzes = $course->quizzes()->available()->whereHas('attempts', function ($q) use ($student) {
            $q->where('student_id', $student->id)
                ->where('status', 'submitted')
                ->whereColumn('percentage', '>=', 'quizzes.passing_score');
        })->count();

        $completedItems = $completedMaterials + $completedQuizzes;

        $percentage = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;

        $enrollment->progress_percentage = max(0, min(100, $percentage));

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
