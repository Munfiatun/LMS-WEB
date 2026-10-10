<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3CStudentAssessmentFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_retryable_failed_attempt_hides_solution_but_points_student_back_to_source_material(): void
    {
        [$student, $quiz, $question, $wrongOption] = $this->assessment(maxAttempts: 2);
        $attempt = app(QuizAttemptService::class)->startAttempt($quiz, $student);
        app(QuizAttemptService::class)->submitAttempt($attempt, [$question->id => $wrongOption->id]);

        $this->actingAs($student)
            ->get(route('student.quizzes.result', [$quiz, $attempt]))
            ->assertOk()
            ->assertSee('Masih ada 1 kesempatan')
            ->assertSee('Coba Lagi Kuis Ini')
            ->assertSee('Tinjau kembali materi pada')
            ->assertSee('Slide 3')
            ->assertDontSee('Merkurius adalah jawaban yang benar.')
            ->assertDontSee('Kunci Jawaban');
    }

    public function test_final_failed_attempt_reveals_verified_solution_and_explanation(): void
    {
        [$student, $quiz, $question, $wrongOption] = $this->assessment(maxAttempts: 1);
        $attempt = app(QuizAttemptService::class)->startAttempt($quiz, $student);
        app(QuizAttemptService::class)->submitAttempt($attempt, [$question->id => $wrongOption->id]);

        $this->actingAs($student)
            ->get(route('student.quizzes.result', [$quiz, $attempt]))
            ->assertOk()
            ->assertSee('Kunci Jawaban')
            ->assertSee('Merkurius')
            ->assertSee('Merkurius adalah jawaban yang benar.')
            ->assertDontSee('Coba Lagi Kuis Ini');
    }

    /** @return array{User, Quiz, Question, \App\Models\QuestionOption} */
    private function assessment(int $maxAttempts): array
    {
        $teacherRole = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $studentRole = Role::firstOrCreate(['name' => Role::ROLE_STUDENT], ['label' => 'Student']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id, 'is_active' => true]);
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $course = Course::factory()->create([
            'instructor_id' => $teacher->id,
            'status' => 'published',
        ]);
        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        $bank = QuestionBank::factory()->create([
            'instructor_id' => $teacher->id,
            'course_id' => $course->id,
        ]);
        $question = $bank->questions()->create([
            'question_text' => 'Planet apa yang paling dekat dengan Matahari?',
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'topic' => 'Source: Slide 3',
            'source_slide_number' => 3,
            'source_excerpt' => 'Merkurius adalah planet yang paling dekat dengan Matahari.',
            'difficulty' => Question::DIFFICULTY_EASY,
            'explanation' => 'Merkurius adalah jawaban yang benar.',
            'points' => 10,
            'order' => 1,
            'needs_review' => false,
            'answer_source' => Question::SOURCE_MANUAL,
            'status' => Question::STATUS_APPROVED,
        ]);
        $question->options()->create(['option_text' => 'Merkurius', 'is_correct' => true, 'order' => 1]);
        $wrong = $question->options()->create(['option_text' => 'Venus', 'is_correct' => false, 'order' => 2]);
        $question->options()->create(['option_text' => 'Bumi', 'is_correct' => false, 'order' => 3]);
        $question->options()->create(['option_text' => 'Mars', 'is_correct' => false, 'order' => 4]);

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'title' => 'Quiz Tata Surya',
            'status' => 'published',
            'published_at' => now(),
            'total_questions' => 1,
            'passing_score' => 70,
            'max_attempts' => $maxAttempts,
        ]);
        $quiz->quizQuestions()->create([
            'question_id' => $question->id,
            'points' => 10,
            'order' => 1,
        ]);

        return [$student, $quiz, $question, $wrong];
    }
}