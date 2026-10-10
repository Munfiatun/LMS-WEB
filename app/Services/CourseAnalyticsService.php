<?php

namespace App\Services;

use App\Models\Course;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

class CourseAnalyticsService
{
    /**
     * Build instructor-facing analytics for a course without leaking data from other courses.
     *
     * @return array<string, mixed>
     */
    public function summarize(Course $course): array
    {
        $enrollments = $course->enrollments()
            ->with('student')
            ->orderByDesc('created_at')
            ->get();

        $quizzes = $course->quizzes()
            ->with(['attempts' => fn ($query) => $query
                ->where('status', 'submitted')
                ->with('student')
                ->latest('submitted_at')])
            ->orderBy('id')
            ->get();

        $submittedAttempts = $quizzes
            ->flatMap(fn ($quiz) => $quiz->attempts)
            ->values();

        $passedAttempts = $submittedAttempts->filter(
            fn (QuizAttempt $attempt) => $attempt->percentage !== null
                && $attempt->percentage >= $attempt->quiz->passing_score
        );

        $quizPerformance = $quizzes->map(function ($quiz): array {
            $attempts = $quiz->attempts;
            $passed = $attempts->filter(
                fn (QuizAttempt $attempt) => $attempt->percentage !== null
                    && $attempt->percentage >= $quiz->passing_score
            );

            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'status' => $quiz->status,
                'passing_score' => (float) $quiz->passing_score,
                'attempts' => $attempts->count(),
                'participants' => $attempts->pluck('student_id')->unique()->count(),
                'average_score' => $this->averagePercentage($attempts),
                'pass_rate' => $this->percentage($passed->count(), $attempts->count()),
            ];
        })->values();

        $studentProgress = $enrollments->map(function ($enrollment) use ($submittedAttempts): array {
            $studentAttempts = $submittedAttempts->where('student_id', $enrollment->student_id);

            return [
                'enrollment' => $enrollment,
                'attempts' => $studentAttempts->count(),
                'average_quiz_score' => $this->averagePercentage($studentAttempts),
            ];
        });

        return [
            'totalStudents' => $enrollments->count(),
            'completedStudents' => $enrollments->where('status', 'completed')->count(),
            'averageProgress' => round((float) ($enrollments->avg('progress_percentage') ?? 0), 1),
            'atRiskStudents' => $enrollments
                ->whereIn('status', ['active'])
                ->filter(fn ($enrollment) => (float) $enrollment->progress_percentage < 50)
                ->count(),
            'totalQuizzes' => $quizzes->count(),
            'submittedAttempts' => $submittedAttempts->count(),
            'quizParticipants' => $submittedAttempts->pluck('student_id')->unique()->count(),
            'averageQuizScore' => $this->averagePercentage($submittedAttempts),
            'quizPassRate' => $this->percentage($passedAttempts->count(), $submittedAttempts->count()),
            'quizPerformance' => $quizPerformance,
            'studentProgress' => $studentProgress,
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

    private function percentage(int $numerator, int $denominator): float
    {
        return $denominator === 0
            ? 0.0
            : round(($numerator / $denominator) * 100, 1);
    }
}
