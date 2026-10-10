<?php

namespace App\Services;

use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
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
            ->flatMap(function ($quiz) {
                return $quiz->attempts->each(fn (QuizAttempt $attempt) => $attempt->setRelation('quiz', $quiz));
            })
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
                ->where('status', 'active')
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
     * Build a drill-down view for one enrolled student in the selected course.
     *
     * @return array<string, mixed>
     */
    public function studentDetail(Course $course, User $student): array
    {
        $enrollment = $course->enrollments()
            ->with('student')
            ->where('student_id', $student->id)
            ->firstOrFail();

        $materials = $course->materials()
            ->published()
            ->with([
                'section',
                'progress' => fn ($query) => $query->where('student_id', $student->id),
            ])
            ->get()
            ->sortBy(fn ($material) => sprintf('%08d-%08d', $material->section?->order ?? 0, $material->order ?? 0))
            ->values();

        $materialRows = $materials->map(function ($material): array {
            $progress = $material->progress->first();

            return [
                'id' => $material->id,
                'title' => $material->title,
                'section' => $material->section?->title,
                'completed' => $progress?->status === 'completed',
                'completed_at' => $progress?->completed_at,
            ];
        });

        $quizzes = $course->quizzes()
            ->with(['attempts' => fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('status', 'submitted')
                ->latest('submitted_at')])
            ->orderBy('id')
            ->get();

        $quizRows = $quizzes->map(function ($quiz): array {
            $attempts = $quiz->attempts;
            $latest = $attempts->first();
            $bestScore = $attempts->whereNotNull('percentage')->max('percentage');
            $bestScore = $bestScore === null ? null : (float) $bestScore;

            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'passing_score' => (float) $quiz->passing_score,
                'attempts' => $attempts->count(),
                'best_score' => $bestScore,
                'latest_score' => $latest?->percentage === null ? null : (float) $latest->percentage,
                'latest_submitted_at' => $latest?->submitted_at,
                'passed' => $bestScore !== null && $bestScore >= (float) $quiz->passing_score,
            ];
        })->values();

        $submittedAttempts = $quizzes->flatMap(fn ($quiz) => $quiz->attempts)->values();
        $averageQuizScore = $this->averagePercentage($submittedAttempts);
        $completedMaterials = $materialRows->where('completed', true)->count();
        $passedQuizzes = $quizRows->where('passed', true)->count();

        [$interventionLevel, $interventionMessage] = $this->interventionFor(
            (float) $enrollment->progress_percentage,
            $averageQuizScore,
            $submittedAttempts->count(),
            $completedMaterials,
            $materialRows->count(),
            $passedQuizzes,
            $quizRows->count(),
            $enrollment->status
        );

        return [
            'student' => $student,
            'enrollment' => $enrollment,
            'materialRows' => $materialRows,
            'quizRows' => $quizRows,
            'completedMaterials' => $completedMaterials,
            'totalMaterials' => $materialRows->count(),
            'passedQuizzes' => $passedQuizzes,
            'totalQuizzes' => $quizRows->count(),
            'submittedAttempts' => $submittedAttempts->count(),
            'averageQuizScore' => $averageQuizScore,
            'interventionLevel' => $interventionLevel,
            'interventionMessage' => $interventionMessage,
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

    /**
     * @return array{0: string, 1: string}
     */
    private function interventionFor(
        float $progress,
        float $averageQuizScore,
        int $attemptCount,
        int $completedMaterials,
        int $totalMaterials,
        int $passedQuizzes,
        int $totalQuizzes,
        string $status
    ): array {
        if ($status === 'completed' || $progress >= 100) {
            return ['stable', 'Siswa telah menuntaskan kursus. Pertahankan umpan balik dan berikan pengayaan bila diperlukan.'];
        }

        if ($progress < 50) {
            return ['high', 'Prioritaskan pendampingan. Arahkan siswa menyelesaikan materi yang tertunda sebelum menambah beban assessment.'];
        }

        if ($attemptCount > 0 && $averageQuizScore < 70) {
            return ['high', 'Nilai assessment masih rendah. Tinjau konsep yang belum dikuasai dan berikan latihan terarah sebelum percobaan berikutnya.'];
        }

        if ($completedMaterials < $totalMaterials || $passedQuizzes < $totalQuizzes) {
            return ['medium', 'Progres berjalan, tetapi masih ada aktivitas yang belum tuntas. Lakukan pengingat dan cek hambatan belajar siswa.'];
        }

        return ['low', 'Progres siswa relatif baik. Lanjutkan pemantauan rutin dan beri umpan balik sesuai kebutuhan.'];
    }
}
