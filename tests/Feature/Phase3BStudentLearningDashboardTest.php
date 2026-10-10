<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\StudentLearningDashboardService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3BStudentLearningDashboardTest extends TestCase
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

    public function test_student_can_view_learning_overview_dashboard(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Learning Overview')
            ->assertSee('Fokus Belajar Berikutnya')
            ->assertSee('LMS Feature Showcase')
            ->assertSee('Web Request Lifecycle')
            ->assertSee('Rata-Rata Quiz');
    }

    public function test_demo_student_dashboard_metrics_are_derived_from_enrolled_course_data(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $summary = app(StudentLearningDashboardService::class)->summarize($student);
        $row = $summary['courseRows']->first();

        $this->assertSame(1, $summary['stats']['enrolled_courses']);
        $this->assertSame(0, $summary['stats']['completed_courses']);
        $this->assertEqualsWithDelta(66.7, $summary['stats']['average_progress'], 0.1);
        $this->assertSame(1, $summary['stats']['completed_materials']);
        $this->assertSame(2, $summary['stats']['total_materials']);
        $this->assertSame(1, $summary['stats']['passed_quizzes']);
        $this->assertSame(1, $summary['stats']['total_quizzes']);
        $this->assertSame(1, $summary['stats']['submitted_attempts']);
        $this->assertSame(100.0, $summary['stats']['average_score']);

        $this->assertNotNull($row);
        $this->assertSame('LMS Feature Showcase', $row['course']->title);
        $this->assertSame('Web Request Lifecycle', $row['next_material']->title);
        $this->assertSame('material', $row['recommendation_type']);
        $this->assertStringContainsString('Web Request Lifecycle', $row['recommendation']);
    }

    public function test_dashboard_excludes_courses_that_are_no_longer_published(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $course->update(['status' => Course::STATUS_ARCHIVED]);

        $summary = app(StudentLearningDashboardService::class)->summarize($student);

        $this->assertSame(0, $summary['stats']['enrolled_courses']);
        $this->assertSame(0, $summary['stats']['completed_materials']);
        $this->assertSame(0, $summary['stats']['submitted_attempts']);
        $this->assertCount(0, $summary['courseRows']);
    }

    public function test_other_students_attempts_do_not_change_current_student_metrics(): void
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
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(5),
            'submitted_at' => now(),
            'status' => 'submitted',
            'score' => 0,
            'percentage' => 0,
            'correct_count' => 0,
            'wrong_count' => 3,
            'duration_seconds' => 600,
        ]);

        $summary = app(StudentLearningDashboardService::class)->summarize($student);

        $this->assertSame(1, $summary['stats']['submitted_attempts']);
        $this->assertSame(100.0, $summary['stats']['average_score']);
        $this->assertSame(1, $summary['stats']['passed_quizzes']);
    }

    public function test_enrolled_student_can_view_course_progress_detail(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.courses.progress', $course))
            ->assertOk()
            ->assertSee('Learning Progress')
            ->assertSee('Rekomendasi Belajar')
            ->assertSee('Web Request Lifecycle')
            ->assertSee('Web Fundamentals Quiz')
            ->assertSee('Buka Materi Berikutnya');
    }

    public function test_course_progress_detail_metrics_are_course_scoped(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $detail = app(StudentLearningDashboardService::class)->courseDetail($student, $course);

        $this->assertSame(1, $detail['completedMaterials']);
        $this->assertSame(2, $detail['totalMaterials']);
        $this->assertSame(1, $detail['passedQuizzes']);
        $this->assertSame(1, $detail['totalQuizzes']);
        $this->assertSame(1, $detail['submittedAttempts']);
        $this->assertSame(100.0, $detail['averageQuizScore']);
        $this->assertSame('Web Request Lifecycle', $detail['nextMaterial']->title);
        $this->assertSame('material', $detail['recommendationType']);
    }

    public function test_non_enrolled_student_cannot_view_course_progress_detail(): void
    {
        $this->seedDemo();

        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();
        $otherStudent = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($otherStudent)
            ->get(route('student.courses.progress', $course))
            ->assertNotFound();
    }

    public function test_archived_course_progress_detail_is_not_available_to_student(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $course->update(['status' => Course::STATUS_ARCHIVED]);

        $this->actingAs($student)
            ->get(route('student.courses.progress', $course))
            ->assertNotFound();
    }
}
