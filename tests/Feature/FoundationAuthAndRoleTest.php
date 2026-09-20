<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationAuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_public_pages_are_accessible(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN)->firstOrFail();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_instructor_can_login_and_is_redirected_to_instructor_dashboard(): void
    {
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $instructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'email' => $instructor->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('instructor.dashboard'));
        $this->assertAuthenticatedAs($instructor);
    }

    public function test_student_can_login_and_is_redirected_to_student_dashboard(): void
    {
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();
        $student = User::factory()->create([
            'role_id' => $studentRole->id,
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'email' => $student->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_student_registration_creates_student_and_logs_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Student',
            'email' => 'student.new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'student.new@example.com',
            'name' => 'New Student',
        ]);

        $user = User::where('email', 'student.new@example.com')->firstOrFail();
        $this->assertTrue($user->isStudent());
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $studentRole->id,
            'password' => bcrypt('secret123'),
            'is_active' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('instructor.dashboard'))->assertRedirect(route('login'));
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    }

    public function test_role_authorization_prevents_unauthorized_access(): void
    {
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);

        // Student cannot access admin or instructor dashboard
        $this->actingAs($student)->get(route('admin.dashboard'))->assertRedirect();
        $this->actingAs($student)->get(route('instructor.dashboard'))->assertRedirect();
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();

        // Instructor cannot access admin or student dashboard
        $this->actingAs($instructor)->get(route('admin.dashboard'))->assertRedirect();
        $this->actingAs($instructor)->get(route('student.dashboard'))->assertRedirect();
        $this->actingAs($instructor)->get(route('instructor.dashboard'))->assertOk();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN)->firstOrFail();
        $admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_user_can_logout(): void
    {
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $response = $this->actingAs($student)->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
