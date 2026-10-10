<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentLearningDashboardService
{
    /**
     * Build a student-facing learning overview using only courses the student is actively enrolled in.
     *
     * @return array<string, mixed>
     */
    public function summarize(User $student): array
    {
        $enrollments = $student->courseEnrollments()
            ->whereHas('course', fn ($query) => $query->published())
            ->whereIn('status', ['active', 'completed'])
            ->with(['course.category'])
            ->orderByDesc('updated_at')
            ->get();

        $courseRows = $enrollments->map(function ($enrollment) use ($student): array {
            $course = $enrollment->course;

            $materials = $course->materials()
                ->published()
                ->with(['section', 'progress' => fn ($query) => $query->where('student_id', $student->id)])
                ->get()
                ->sortBy(fn (LearningMaterial $material) => sprintf(
                    '%08d-%08d',
                    $material->section?->order ?? 0,
                    $material->order ?? 0
                ))
                ->values();

            $quizzes = $course->quizzes()
                ->available()
                ->with(['attempts' => fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->where('status', 'submitted')
                    ->latest('submitted_at')])
                ->orderBy('id')
                ->get();

            $completedMaterials = $materials->filter(
                fn (LearningMaterial $material) => $material->progress->first()?->status === 'completed'
            );
            $unfinishedMaterial = $materials->first(
                fn (LearningMaterial $material) => $material->progress->first()?->status !== 'completed'
            );

            $submittedAttempts = $quizzes
                ->flatMap(function (Quiz $quiz) {
                    return $quiz->attempts->each(fn (QuizAttempt $attempt) => $attempt->setRelation('quiz', $quiz));
                })
                ->values();

            $passedQuizIds = $submittedAttempts
                ->filter(fn (QuizAttempt $attempt) => $attempt->percentage !== null
                    && $attempt->percentage >= (float) $attempt->quiz->passing_score)
                ->pluck('quiz_id')
                ->unique();

            $pendingQuiz = $quizzes->first(fn (Quiz $quiz) => ! $passedQuizIds->contains($quiz->id));

            [$recommendation, $recommendationType] = $this->recommendationFor(
                (float) $enrollment->progress_percentage,
                $unfinishedMaterial,
                $pendingQuiz,
                $submittedAttempts
            );

            return [
                'enrollment' => $enrollment,
                'course' => $course,
                'completed_materials' => $completedMaterials->count(),
                'total_materials' => $materials->count(),
                'passed_quizzes' => $passedQuizIds->count(),
                'total_quizzes' => $quizzes->count(),
                'submitted_attempts' => $submittedAttempts->count(),
                'average_quiz_score' => $this->averagePercentage($submittedAttempts),
                'next_material' => $unfinishedMaterial,
                'next_quiz' => $pendingQuiz,
                'recommendation' => $recommendation,
                'recommendation_type' => $recommendationType,
            ];
        })->values();

        $allAttempts = $courseRows
            ->flatMap(function (array $row) use ($student) {
                return QuizAttempt::query()
                    ->where('student_id', $student->id)
                    ->where('status', 'submitted')
                    ->whereHas('quiz', fn ($query) => $query->where('course_id', $row['course']->id))
                    ->get();
            })
            ->unique('id')
            ->values();

        return [
            'enrollments' => $enrollments,
            'courseRows' => $courseRows,
            'stats' => [
                'enrolled_courses' => $courseRows->count(),
                'completed_courses' => $enrollments->where('status', 'completed')->count(),
                'average_progress' => round((float) ($enrollments->avg('progress_percentage') ?? 0), 1),
                'completed_materials' => $courseRows->sum('completed_materials'),
                'total_materials' => $courseRows->sum('total_materials'),
                'passed_quizzes' => $courseRows->sum('passed_quizzes'),
                'total_quizzes' => $courseRows->sum('total_quizzes'),
                'submitted_attempts' => $allAttempts->count(),
                'average_score' => $this->averagePercentage($allAttempts),
            ],
        ];
    }

    /**
     * @param  Collection<int, QuizAttempt>  $attempts
     */
    private function averagePercentage(Collection $attempts): float
    {
        $scored = $attempts->filter(fn (QuizAttempt $attempt) => $attempt->percentage !== null);

        return $scored->isEmpty()
            ? 0.0
            : round((float) $scored->avg('percentage'), 1);
    }

    /**
     * @param  Collection<int, QuizAttempt>  $attempts
     * @return array{0: string, 1: string}
     */
    private function recommendationFor(
        float $progress,
        ?LearningMaterial $unfinishedMaterial,
        ?Quiz $pendingQuiz,
        Collection $attempts
    ): array {
        if ($progress >= 100) {
            return ['Kursus sudah tuntas. Tinjau kembali materi atau lanjutkan ke kursus lain.', 'complete'];
        }

        if ($unfinishedMaterial) {
            return ["Lanjutkan materi: {$unfinishedMaterial->title}", 'material'];
        }

        if ($pendingQuiz) {
            return ["Kerjakan atau ulangi quiz: {$pendingQuiz->title}", 'quiz'];
        }

        if ($attempts->isNotEmpty()) {
            return ['Pertahankan progres dan tinjau kembali hasil quiz yang sudah dikerjakan.', 'review'];
        }

        return ['Mulai dari materi pertama untuk membangun progres belajar.', 'start'];
    }
}
