<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use App\Services\Course\CourseService;
use App\Services\EnrollmentService;
use App\Services\MaterialService;
use App\Services\Quiz\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkflowStabilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_and_material_create_and_update_cannot_bypass_publication(): void
    {
        $teacher = $this->user('instructor');
        $this->actingAs($teacher)->post(route('instructor.courses.store'), ['title' => 'New Course'])->assertRedirect();
        $course = Course::sole();
        $this->assertSame('draft', $course->status);
        $this->put(route('instructor.courses.update', $course), ['title' => 'Updated'])->assertRedirect();
        $this->assertSame('draft', $course->fresh()->status);
        app(CourseService::class)->updateCourse($course, ['status' => 'published', 'published_at' => now()]);
        $this->assertSame('draft', $course->fresh()->status);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);

        $this->post(route('instructor.materials.store', $section), ['title' => 'New Material', 'content' => 'Text content'])->assertRedirect();
        $material = LearningMaterial::sole();
        $this->assertSame('draft', $material->status);
        $this->put(route('instructor.materials.update', $material), ['title' => 'Updated'])->assertRedirect();
        $this->assertSame('draft', $material->fresh()->status);
        app(MaterialService::class)->updateMaterial($material, ['status' => 'published']);
        $this->assertSame('draft', $material->fresh()->status);
        $this->assertNull($material->fresh()->published_at);
    }

    public function test_valid_material_then_course_can_publish_and_archive_explicitly(): void
    {
        [$teacher, $course, $section, $material] = $this->lesson();
        $this->actingAs($teacher)->post(route('instructor.materials.publish', $material))->assertSessionHas('success');
        $this->assertSame('published', $material->fresh()->status);
        $this->get(route('instructor.courses.edit', $course))->assertOk()->assertSee('Publikasikan Kursus');
        $this->post(route('instructor.courses.publish', $course))->assertSessionHas('success');
        $this->assertSame('published', $course->fresh()->status);
        $this->post(route('instructor.courses.archive', $course))->assertSessionHas('success');
        $this->assertSame('archived', $course->fresh()->status);
        $this->postJson(route('instructor.courses.publish', $course))->assertUnprocessable()->assertJsonValidationErrors('course');
    }

    #[DataProvider('invalidMaterialStates')]
    public function test_invalid_material_cannot_publish_or_show_publish_button(string $invalid): void
    {
        [$teacher, $course, $section, $material] = $this->lesson();
        match ($invalid) {
            'empty' => $material->update(['content' => null]),
            'processing' => $material->update(['status' => 'processing']),
            'archived course' => $course->update(['status' => 'archived']),
            'inactive section' => $section->update(['status' => 'archived']),
            'inactive instructor' => $teacher->update(['is_active' => false]),
        };
        if ($invalid === 'inactive instructor') {
            $teacher = $this->user('admin');
            try {
                app(MaterialService::class)->publishMaterial($material->fresh());
                $this->fail('Invalid material must not publish.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('material', $exception->errors());
            }
        } else {
            $this->actingAs($teacher)->postJson(route('instructor.materials.publish', $material))->assertUnprocessable()->assertJsonValidationErrors('material');
            $this->get(route('instructor.materials.edit', $material))->assertOk()->assertDontSee('Publikasikan Materi');
        }
        $this->assertNotSame('published', $material->fresh()->status);
    }

    public static function invalidMaterialStates(): array
    {
        return [['empty'], ['processing'], ['archived course'], ['inactive section'], ['inactive instructor']];
    }

    public function test_course_with_only_draft_material_cannot_publish(): void
    {
        [$teacher, $course] = $this->lesson();
        $this->actingAs($teacher)->postJson(route('instructor.courses.publish', $course))->assertUnprocessable()->assertJsonValidationErrors('course');
        $this->assertSame('draft', $course->fresh()->status);
        $this->get(route('instructor.courses.edit', $course))->assertDontSee('Publikasikan Kursus');
    }

    public function test_published_material_requires_explicit_unpublish_before_editing(): void
    {
        [$teacher, $course, $section, $material] = $this->lesson();
        app(MaterialService::class)->publishMaterial($material);
        $this->actingAs($teacher)->putJson(route('instructor.materials.update', $material), ['title' => 'Mutated', 'content' => 'Changed'])
            ->assertUnprocessable()->assertJsonValidationErrors('material');
        $this->assertSame('Lesson content', $material->fresh()->content);
        $this->post(route('instructor.materials.unpublish', $material))->assertSessionHas('success');
        $this->put(route('instructor.materials.update', $material), ['title' => 'Reviewed edit', 'content' => 'Changed'])->assertSessionHas('success');
        $this->assertSame('draft', $material->fresh()->status);
        $this->assertSame('Changed', $material->fresh()->content);
    }

    #[DataProvider('courseStatuses')]
    public function test_enrollment_requires_published_course_even_with_valid_code(string $status): void
    {
        [$teacher, $course] = $this->lesson();
        $student = $this->user('student');
        $course->update(['status' => $status, 'enrollment_code' => 'VALID-CODE']);
        $response = $this->actingAs($student)->postJson(route('student.courses.enroll', $course), ['enrollment_code' => 'VALID-CODE']);
        if ($status === 'published') {
            $response->assertRedirect(route('student.courses.index'));
            $this->post(route('student.courses.enroll', $course), ['enrollment_code' => 'VALID-CODE'])->assertSessionHas('info', 'Anda sudah terdaftar pada kelas ini.');
            $this->assertDatabaseCount('course_enrollments', 1);
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('enrollment_code');
            $this->assertDatabaseCount('course_enrollments', 0);
        }
    }

    public static function courseStatuses(): array
    {
        return [['draft'], ['published'], ['archived']];
    }

    public function test_student_lists_exclude_unpublished_courses_and_quizzes(): void
    {
        [$teacher, $course] = $this->lesson();
        $student = $this->user('student');
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id]);
        $quiz = Quiz::factory()->create(['course_id' => $course->id, 'status' => 'published']);
        $this->actingAs($student)->get(route('student.courses.index'))->assertOk()->assertDontSee($course->title);
        $this->get(route('student.dashboard'))->assertOk()->assertDontSee($course->title);
        $this->get(route('student.quizzes.index'))->assertOk()->assertDontSee($quiz->title);
        $this->post(route('student.quizzes.start', $quiz))->assertForbidden();
        $course->update(['status' => 'published']);
        $course->enrollments()->update(['status' => 'dropped']);
        $this->post(route('student.quizzes.start', $quiz))->assertForbidden();
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_quiz_status_cannot_bypass_central_publish_validation(): void
    {
        [$teacher, $course] = $this->lesson();
        $quiz = app(QuizService::class)->createQuiz($course, [
            'title' => 'New Quiz', 'passing_score' => 70, 'total_questions' => 1, 'max_attempts' => 1, 'status' => 'published',
        ]);
        $this->assertSame('draft', $quiz->status);
        app(QuizService::class)->updateQuiz($quiz, ['status' => 'published', 'published_at' => now()]);
        $this->assertSame('draft', $quiz->fresh()->status);
        $this->actingAs($teacher)->postJson(route('instructor.quizzes.publish', $quiz))->assertUnprocessable()->assertJsonValidationErrors('quiz');
        $this->assertSame('draft', $quiz->fresh()->status);
    }

    public function test_reviewed_valid_quiz_publishes_and_cannot_be_mutated_without_explicit_action(): void
    {
        [$teacher, $course] = $this->lesson();
        [$quiz, $question] = $this->quiz($teacher, $course);
        $this->actingAs($teacher)->post(route('instructor.quizzes.publish', $quiz))->assertSessionHas('success');
        $this->assertSame('published', $quiz->fresh()->status);
        $this->postJson(route('instructor.quizzes.sync-questions', $quiz), ['questions' => [['id' => $question->id, 'points' => 20, 'order' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('quiz');
        $this->deleteJson(route('instructor.questions.destroy', $question))->assertUnprocessable()->assertJsonValidationErrors('question');
        $this->assertModelExists($question);
        $this->assertSame('published', $quiz->fresh()->status);
        $this->post(route('instructor.quizzes.unpublish', $quiz))->assertSessionHas('success');
        $this->assertSame('draft', $quiz->fresh()->status);
    }

    #[DataProvider('invalidQuizzes')]
    public function test_invalid_quiz_cannot_publish(string $invalid): void
    {
        [$teacher, $course] = $this->lesson();
        [$quiz, $question] = $this->quiz($teacher, $course);
        match ($invalid) {
            'review' => $question->update(['needs_review' => true]),
            'unapproved' => $question->update(['status' => 'draft']),
            'no answer' => $question->options()->update(['is_correct' => false]),
            'two answers' => $question->options()->update(['is_correct' => true]),
            'passing score' => $quiz->update(['passing_score' => 101]),
            'section mismatch' => $quiz->update(['section_id' => CourseSection::factory()->create(['course_id' => Course::factory()->create(['instructor_id' => $teacher->id])->id])->id]),
        };
        $this->actingAs($teacher)->postJson(route('instructor.quizzes.publish', $quiz))->assertUnprocessable()->assertJsonValidationErrors('quiz');
        $this->assertSame('draft', $quiz->fresh()->status);
        $this->get(route('instructor.quizzes.show', $quiz))->assertOk()->assertDontSee('action="'.route('instructor.quizzes.publish', $quiz).'"', false);
    }

    public static function invalidQuizzes(): array
    {
        return [['review'], ['unapproved'], ['no answer'], ['two answers'], ['passing score'], ['section mismatch']];
    }

    public function test_progress_excludes_inactive_content_and_invalid_attempts(): void
    {
        [$teacher, $course, $section, $material] = $this->lesson();
        $course->update(['status' => 'published']);
        $material->update(['status' => 'published']);
        $student = $this->user('student');
        $enrollment = app(EnrollmentService::class)->enrollStudent($course, $student);
        $draft = LearningMaterial::factory()->create(['section_id' => $section->id, 'status' => 'draft']);
        $inactiveSection = CourseSection::factory()->create(['course_id' => $course->id, 'status' => 'archived']);
        $inactive = LearningMaterial::factory()->create(['section_id' => $inactiveSection->id, 'status' => 'published']);
        $deleted = LearningMaterial::factory()->create(['section_id' => $section->id, 'status' => 'published']);
        foreach ([$material, $draft, $inactive, $deleted] as $item) {
            MaterialProgress::create(['learning_material_id' => $item->id, 'student_id' => $student->id, 'status' => 'completed']);
        }
        $deleted->delete();
        $quiz = Quiz::factory()->create(['course_id' => $course->id, 'status' => 'published']);
        $draftQuiz = Quiz::factory()->create(['course_id' => $course->id, 'status' => 'draft']);
        foreach ([[$quiz, 'expired'], [$quiz, 'in_progress'], [$draftQuiz, 'submitted']] as [$item, $status]) {
            $item->attempts()->create(['student_id' => $student->id, 'status' => $status, 'percentage' => 100, 'started_at' => now(), 'expires_at' => now()->addHour()]);
        }
        app(EnrollmentService::class)->updateProgress($course, $student);
        $this->assertSame('50.00', $enrollment->fresh()->progress_percentage);
        $quiz->attempts()->create(['student_id' => $student->id, 'status' => 'submitted', 'percentage' => 100, 'started_at' => now(), 'expires_at' => now()->addHour()]);
        app(EnrollmentService::class)->updateProgress($course, $student);
        $this->assertSame('100.00', $enrollment->fresh()->progress_percentage);
        $this->assertSame('completed', $enrollment->fresh()->status);
    }

    public function test_empty_course_progress_is_zero_and_dropped_enrollment_is_not_reactivated(): void
    {
        [$teacher, $course] = $this->lesson();
        $course->update(['status' => 'published']);
        $student = $this->user('student');
        $enrollment = app(EnrollmentService::class)->enrollStudent($course, $student);
        app(EnrollmentService::class)->updateProgress($course, $student);
        $this->assertSame('0.00', $enrollment->fresh()->progress_percentage);
        $this->assertSame('active', $enrollment->fresh()->status);
        $enrollment->update(['status' => 'dropped']);
        app(EnrollmentService::class)->updateProgress($course, $student);
        $this->assertSame('dropped', $enrollment->fresh()->status);
    }

    /** @return array{User, Course, CourseSection, LearningMaterial} */
    private function lesson(): array
    {
        $teacher = $this->user('instructor');
        $course = Course::factory()->create(['instructor_id' => $teacher->id, 'status' => 'draft']);
        $section = CourseSection::factory()->create(['course_id' => $course->id, 'status' => 'active']);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id, 'status' => 'draft', 'content' => 'Lesson content']);

        return [$teacher, $course, $section, $material];
    }

    /** @return array{Quiz, Question} */
    private function quiz(User $teacher, Course $course): array
    {
        $quiz = Quiz::factory()->create(['course_id' => $course->id, 'total_questions' => 1]);
        $bank = QuestionBank::factory()->create(['instructor_id' => $teacher->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id, 'status' => 'approved', 'needs_review' => false, 'type' => 'multiple_choice']);
        $question->options()->createMany([['option_text' => 'Correct', 'is_correct' => true], ['option_text' => 'Wrong', 'is_correct' => false]]);
        $quiz->quizQuestions()->create(['question_id' => $question->id, 'points' => 10, 'order' => 1]);

        return [$quiz, $question];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role], ['label' => $role])->id, 'is_active' => true]);
    }
}
