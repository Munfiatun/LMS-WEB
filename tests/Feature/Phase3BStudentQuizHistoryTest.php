<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3BStudentQuizHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): void
    {
        config(['auth.demo_user_password' => 'demo-test-password']);
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(LmsDemoSeeder::class);
    }

    public function test_student_can_view_quiz_summary_and_recent_history(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.quizzes.index'))
            ->assertOk()
            ->assertSee('Assessment Overview')
            ->assertSee('Riwayat Nilai Terbaru')
            ->assertSee('Web Fundamentals Quiz')
            ->assertSee('100.0%')
            ->assertSee('Lulus')
            ->assertSee('1 attempt submitted');
    }

    public function test_other_students_attempts_are_not_included_in_quiz_history_metrics(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $quiz = Quiz::where('course_id', $course->id)->where('title', 'Web Fundamentals Quiz')->firstOrFail();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();
        $otherStudent = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $otherStudent->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $otherStudent->id,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(5),
            'submitted_at' => now(),
            'status' => 'submitted',
            'score' => 0,
            'percentage' => 0,
            'correct_count' => 0,
            'wrong_count' => 3,
            'duration_seconds' => 300,
        ]);

        $response = $this->actingAs($student)->get(route('student.quizzes.index'));

        $response->assertOk()
            ->assertSee('1 attempt submitted')
            ->assertSee('100.0%')
            ->assertDontSee('0.0%');
    }

    public function test_archived_course_quiz_is_removed_from_student_quiz_overview(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $course->update(['status' => Course::STATUS_ARCHIVED]);

        $this->actingAs($student)
            ->get(route('student.quizzes.index'))
            ->assertOk()
            ->assertSee('Belum ada kuis yang tersedia.')
            ->assertDontSee('Web Fundamentals Quiz');
    }
}
