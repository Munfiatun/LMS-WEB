<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_take_and_submit_quiz(): void
    {
        $instructorRole = Role::create(['name' => Role::ROLE_INSTRUCTOR, 'label' => 'Instructor']);
        $studentRole = Role::create(['name' => Role::ROLE_STUDENT, 'label' => 'Student']);

        $instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => 'published',
        ]);

        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'status' => 'published',
            'duration_minutes' => 60,
            'total_questions' => 2,
            'passing_score' => 50,
            'max_attempts' => 2,
        ]);

        $bank = QuestionBank::factory()->create(['instructor_id' => $instructor->id]);

        $question1 = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'type' => 'multiple_choice',
            'needs_review' => false,
        ]);
        $opt1A = QuestionOption::factory()->create(['question_id' => $question1->id, 'is_correct' => true]);
        $opt1B = QuestionOption::factory()->create(['question_id' => $question1->id, 'is_correct' => false]);

        $question2 = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'type' => 'multiple_choice',
            'needs_review' => false,
        ]);
        $opt2A = QuestionOption::factory()->create(['question_id' => $question2->id, 'is_correct' => true]);
        $opt2B = QuestionOption::factory()->create(['question_id' => $question2->id, 'is_correct' => false]);

        $quiz->quizQuestions()->create(['question_id' => $question1->id, 'points' => 10, 'order' => 1]);
        $quiz->quizQuestions()->create(['question_id' => $question2->id, 'points' => 10, 'order' => 2]);

        \App\Models\CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        // Student visits quiz intro
        $response = $this->actingAs($student)->get(route('student.quizzes.show', $quiz));
        $response->assertStatus(200);

        // Student starts quiz
        $response = $this->actingAs($student)->post(route('student.quizzes.start', $quiz));
        $response->assertRedirect();

        $attempt = $quiz->attempts()->first();
        $this->assertNotNull($attempt);
        $this->assertEquals('in_progress', $attempt->status);

        // Student submits quiz
        $response = $this->actingAs($student)->post(route('student.quizzes.submit', ['quiz' => $quiz->id, 'attempt' => $attempt->id]), [
            'answers' => [
                $question1->id => $opt1A->id, // Correct
                $question2->id => $opt2B->id, // Incorrect
            ],
        ]);
        $response->assertRedirect(route('student.quizzes.result', ['quiz' => $quiz->id, 'attempt' => $attempt->id]));

        $attempt->refresh();
        $this->assertEquals('submitted', $attempt->status);
        $this->assertEquals(10, $attempt->score); // Only 1 correct (10 points)
        $this->assertEquals(50, $attempt->percentage); // 1 out of 2 (50%)
        $this->assertEquals(1, $attempt->correct_count);
        $this->assertEquals(1, $attempt->wrong_count);
    }
}
