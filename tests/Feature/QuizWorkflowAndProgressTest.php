<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\Quiz\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizWorkflowAndProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private Course $course;

    private CourseSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $instructorRole = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $studentRole = Role::firstOrCreate(['name' => Role::ROLE_STUDENT], ['label' => 'Student']);

        $this->instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $this->course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => Course::STATUS_PUBLISHED,
            'enrollment_code' => 'VALID-CODE',
        ]);

        $this->section = CourseSection::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
    }

    // ───── QUIZ LIFECYCLE ─────

    public function test_new_quiz_defaults_to_draft(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.quizzes.store'), [
                'course_id' => $this->course->id,
                'section_id' => $this->section->id,
                'title' => 'Test Quiz',
                'passing_score' => 70,
                'total_questions' => 1,
                'max_attempts' => 1,
                'randomize_questions' => 0,
                'randomize_options' => 0,
            ])->assertSessionHas('success');

        $quiz = Quiz::where('title', 'Test Quiz')->first();
        $this->assertNotNull($quiz);
        $this->assertSame('draft', $quiz->status);
    }

    public function test_create_request_cannot_force_published(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.quizzes.store'), [
                'course_id' => $this->course->id,
                'title' => 'Bypass Quiz',
                'passing_score' => 70,
                'total_questions' => 1,
                'max_attempts' => 1,
                'randomize_questions' => 0,
                'randomize_options' => 0,
                'status' => 'published', // Malicious attempt
            ])->assertSessionHasErrors(['status']);

        $this->assertDatabaseMissing('quizzes', ['title' => 'Bypass Quiz']);
    }

    public function test_quiz_with_needs_review_question_cannot_publish(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(true); // needs_review = true

        $this->actingAs($this->instructor)
            ->post(route('instructor.quizzes.publish', $quiz))
            ->assertSessionHasErrors(['quiz']);

        $this->assertSame('draft', $quiz->fresh()->status);
    }

    public function test_quiz_with_invalid_answers_cannot_publish(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, false); // no correct option

        $this->actingAs($this->instructor)
            ->post(route('instructor.quizzes.publish', $quiz))
            ->assertSessionHasErrors(['quiz']);

        $this->assertSame('draft', $quiz->fresh()->status);
    }

    public function test_valid_reviewed_quiz_can_publish_through_official_action(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, true);

        $this->actingAs($this->instructor)
            ->post(route('instructor.quizzes.publish', $quiz))
            ->assertSessionHas('success');

        $this->assertSame('published', $quiz->fresh()->status);
        $this->assertNotNull($quiz->fresh()->published_at);
    }

    public function test_draft_or_unpublished_quiz_cannot_be_started_by_student(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, true);

        // Ensure student is enrolled
        app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        $this->actingAs($this->student)
            ->post(route('student.quizzes.start', $quiz))
            ->assertForbidden();

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_published_valid_quiz_can_be_started_by_authorized_enrolled_student(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, true);
        app(QuizService::class)->publishQuiz($quiz);
        app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        $this->actingAs($this->student)
            ->post(route('student.quizzes.start', $quiz))
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);
    }

    // ───── ENROLLMENT ─────

    public function test_published_course_can_be_enrolled(): void
    {
        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->course), ['enrollment_code' => 'VALID-CODE'])
            ->assertRedirect();

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_draft_course_cannot_be_enrolled(): void
    {
        $this->course->update(['status' => Course::STATUS_DRAFT]);

        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->course), ['enrollment_code' => 'VALID-CODE'])
            ->assertSessionHasErrors(['enrollment_code']);

        $this->assertDatabaseMissing('course_enrollments', [
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_archived_course_cannot_be_enrolled(): void
    {
        $this->course->update(['status' => Course::STATUS_ARCHIVED]);

        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->course), ['enrollment_code' => 'VALID-CODE'])
            ->assertSessionHasErrors(['enrollment_code']);
    }

    public function test_duplicate_enrollment_does_not_create_duplicate_active_enrollment(): void
    {
        app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->course), ['enrollment_code' => 'VALID-CODE'])
            ->assertSessionHas('info');

        $this->assertDatabaseCount('course_enrollments', 1);
    }

    // ───── PROGRESS ─────

    public function test_draft_material_excluded_from_progress(): void
    {
        app(EnrollmentService::class)->enrollStudent($this->course, $this->student);
        LearningMaterial::factory()->create([
            'section_id' => $this->section->id,
            'status' => LearningMaterial::STATUS_DRAFT,
        ]);

        app(EnrollmentService::class)->updateProgress($this->course, $this->student);

        $enrollment = CourseEnrollment::first();
        // Since no valid materials/quizzes, progress is 0. But it proves draft is excluded from calculation.
        // Let's add a published material and see if max items = 1.
        $pubMat = LearningMaterial::factory()->create([
            'section_id' => $this->section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);

        app(EnrollmentService::class)->markMaterialCompleted($pubMat, $this->student);
        // Progress should be 100% (1 completed / 1 valid), not 50%

        $this->assertSame('100.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_unpublished_material_excluded_from_progress(): void
    {
        app(EnrollmentService::class)->enrollStudent($this->course, $this->student);
        $mat = LearningMaterial::factory()->create([
            'section_id' => $this->section->id,
            'status' => LearningMaterial::STATUS_ARCHIVED,
        ]);

        $pubMat = LearningMaterial::factory()->create([
            'section_id' => $this->section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);

        app(EnrollmentService::class)->markMaterialCompleted($pubMat, $this->student);
        $enrollment = CourseEnrollment::first();
        $this->assertSame('100.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_draft_and_unpublished_quizzes_excluded_from_progress(): void
    {
        app(EnrollmentService::class)->enrollStudent($this->course, $this->student);
        $quiz = $this->createDraftQuizWithQuestion(false, true);

        $pubMat = LearningMaterial::factory()->create([
            'section_id' => $this->section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);

        app(EnrollmentService::class)->markMaterialCompleted($pubMat, $this->student);
        $enrollment = CourseEnrollment::first();
        // Progress should be 100% because draft quiz is not counted in total
        $this->assertSame('100.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_expired_and_in_progress_quiz_attempts_excluded_from_completion(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, true);
        app(QuizService::class)->publishQuiz($quiz);
        $enrollment = app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        // In progress
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'percentage' => 100, // Even if it somehow has percentage
        ]);
        app(EnrollmentService::class)->updateProgress($this->course, $this->student);
        $this->assertSame('0.00', $enrollment->fresh()->progress_percentage);

        // Expired
        $attempt->update(['status' => 'expired']);
        app(EnrollmentService::class)->updateProgress($this->course, $this->student);
        $this->assertSame('0.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_valid_submitted_passing_attempt_counts(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, true);
        app(QuizService::class)->publishQuiz($quiz);
        $enrollment = app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'submitted_at' => now(),
            'percentage' => $quiz->passing_score,
        ]);

        app(EnrollmentService::class)->updateProgress($this->course, $this->student);
        $this->assertSame('100.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_failed_submitted_attempt_does_not_count_as_completion(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, true);
        app(QuizService::class)->publishQuiz($quiz);
        $enrollment = app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'submitted_at' => now(),
            'percentage' => $quiz->passing_score - 10,
        ]);

        app(EnrollmentService::class)->updateProgress($this->course, $this->student);
        $this->assertSame('0.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_multiple_attempts_do_not_double_count_quiz_completion(): void
    {
        $quiz = $this->createDraftQuizWithQuestion(false, true);
        app(QuizService::class)->publishQuiz($quiz);
        $enrollment = app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        // Add 3 passing attempts
        for ($i = 0; $i < 3; $i++) {
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $this->student->id,
                'status' => 'submitted',
                'started_at' => now(),
                'expires_at' => now()->addHour(),
                'submitted_at' => now(),
                'percentage' => 100,
            ]);
        }

        app(EnrollmentService::class)->updateProgress($this->course, $this->student);
        // It shouldn't exceed 100% or count as 3 quizzes completed
        $this->assertSame('100.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_empty_course_progress_is_safe(): void
    {
        $enrollment = app(EnrollmentService::class)->enrollStudent($this->course, $this->student);
        app(EnrollmentService::class)->updateProgress($this->course, $this->student);

        $this->assertSame('0.00', $enrollment->fresh()->progress_percentage);
    }

    public function test_mixed_material_and_quiz_progress_calculation_is_correct(): void
    {
        $enrollment = app(EnrollmentService::class)->enrollStudent($this->course, $this->student);

        $pubMat = LearningMaterial::factory()->create([
            'section_id' => $this->section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);

        $quiz = $this->createDraftQuizWithQuestion(false, true);
        app(QuizService::class)->publishQuiz($quiz);

        // 0/2 completed = 0%
        app(EnrollmentService::class)->updateProgress($this->course, $this->student);
        $this->assertSame('0.00', $enrollment->fresh()->progress_percentage);

        // 1/2 completed = 50%
        app(EnrollmentService::class)->markMaterialCompleted($pubMat, $this->student);
        $this->assertSame('50.00', $enrollment->fresh()->progress_percentage);

        // 2/2 completed = 100%
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'submitted_at' => now(),
            'percentage' => 100,
        ]);
        app(EnrollmentService::class)->updateProgress($this->course, $this->student);
        $this->assertSame('100.00', $enrollment->fresh()->progress_percentage);
        $this->assertSame('completed', $enrollment->fresh()->status);
    }

    // ───── HELPER ─────

    private function createDraftQuizWithQuestion(bool $needsReview, bool $hasCorrectOption = true): Quiz
    {
        $quiz = app(QuizService::class)->createQuiz($this->course, [
            'title' => 'Sample Quiz',
            'passing_score' => 70,
            'total_questions' => 1,
            'max_attempts' => 1,
            'section_id' => $this->section->id,
            'duration_minutes' => 60,
        ]);

        $bank = QuestionBank::factory()->create(['instructor_id' => $this->instructor->id]);
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'needs_review' => $needsReview,
            'status' => 'approved',
            'type' => 'multiple_choice',
        ]);
        $question->options()->createMany([
            ['option_text' => 'Opt A', 'is_correct' => $hasCorrectOption],
            ['option_text' => 'Opt B', 'is_correct' => false],
        ]);

        app(QuizService::class)->syncQuestions($quiz, [['id' => $question->id, 'points' => 100, 'order' => 1]]);

        return $quiz;
    }
}
