<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Services\CourseAnalyticsService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3AInstructorAnalyticsTest extends TestCase
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

    public function test_course_owner_can_view_extended_analytics(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();

        $this->actingAs($instructor)
            ->get(route('instructor.courses.analytics', $course))
            ->assertOk()
            ->assertSee('Laporan Analitik Kursus')
            ->assertSee('Performa Quiz')
            ->assertSee('Rata-Rata Nilai Quiz')
            ->assertSee('Pass Rate Quiz')
            ->assertSee('Perlu Perhatian')
            ->assertSee('Lihat Detail');
    }

    public function test_other_instructor_cannot_view_private_course_analytics(): void
    {
        $this->seedDemo();

        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $role = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $otherInstructor = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($otherInstructor)
            ->get(route('instructor.courses.analytics', $course))
            ->assertForbidden();
    }

    public function test_demo_analytics_are_derived_from_real_course_data(): void
    {
        $this->seedDemo();

        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $stats = app(CourseAnalyticsService::class)->summarize($course);

        $this->assertSame(1, $stats['totalStudents']);
        $this->assertSame(0, $stats['completedStudents']);
        $this->assertSame(0, $stats['atRiskStudents']);
        $this->assertSame(1, $stats['totalQuizzes']);
        $this->assertSame(1, $stats['submittedAttempts']);
        $this->assertSame(1, $stats['quizParticipants']);
        $this->assertSame(100.0, $stats['averageQuizScore']);
        $this->assertSame(100.0, $stats['quizPassRate']);
        $this->assertEqualsWithDelta(66.7, $stats['averageProgress'], 0.1);

        $this->assertCount(1, $stats['quizPerformance']);
        $this->assertSame('Web Fundamentals Quiz', $stats['quizPerformance'][0]['title']);
        $this->assertSame(100.0, $stats['quizPerformance'][0]['average_score']);
        $this->assertSame(100.0, $stats['quizPerformance'][0]['pass_rate']);
    }

    public function test_course_owner_can_view_enrolled_student_performance_detail(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();

        $this->actingAs($instructor)
            ->get(route('instructor.courses.analytics.student', [$course, $student]))
            ->assertOk()
            ->assertSee('Student Performance Detail')
            ->assertSee($student->name)
            ->assertSee('Rekomendasi Tindak Lanjut')
            ->assertSee('Slidebook Layout Gallery')
            ->assertSee('Web Fundamentals Quiz');
    }

    public function test_student_detail_metrics_are_scoped_to_selected_course(): void
    {
        $this->seedDemo();

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $detail = app(CourseAnalyticsService::class)->studentDetail($course, $student);

        $this->assertSame($student->id, $detail['student']->id);
        $this->assertSame(1, $detail['completedMaterials']);
        $this->assertSame(2, $detail['totalMaterials']);
        $this->assertSame(1, $detail['passedQuizzes']);
        $this->assertSame(1, $detail['totalQuizzes']);
        $this->assertSame(1, $detail['submittedAttempts']);
        $this->assertSame(100.0, $detail['averageQuizScore']);
        $this->assertSame('medium', $detail['interventionLevel']);
    }

    public function test_non_enrolled_student_cannot_be_opened_from_course_analytics(): void
    {
        $this->seedDemo();

        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();
        $otherStudent = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.analytics.student', [$course, $otherStudent]))
            ->assertNotFound();
    }
}
