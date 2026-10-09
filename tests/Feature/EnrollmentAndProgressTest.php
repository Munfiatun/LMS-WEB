<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentAndProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_enroll_and_track_progress(): void
    {
        $instructorRole = Role::create(['name' => Role::ROLE_INSTRUCTOR, 'label' => 'Instructor']);
        $studentRole = Role::create(['name' => Role::ROLE_STUDENT, 'label' => 'Student']);

        $instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => 'published',
        ]);

        $section = CourseSection::factory()->create(['course_id' => $course->id]);

        $material1 = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'status' => 'published',
        ]);

        $material2 = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'status' => 'published',
        ]);

        $course->update(['enrollment_code' => 'TESTCODE']);

        // Student enrolls
        $response = $this->actingAs($student)->post(route('student.courses.enroll', $course), [
            'enrollment_code' => 'TESTCODE',
        ]);
        $response->assertRedirect(route('student.courses.index'));
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        // Student completes material 1
        $response = $this->actingAs($student)->post(route('student.materials.complete', $material1));
        $this->assertDatabaseHas('material_progress', [
            'student_id' => $student->id,
            'learning_material_id' => $material1->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'progress_percentage' => 50,
        ]);

        // Student completes material 2
        $response = $this->actingAs($student)->post(route('student.materials.complete', $material2));
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'progress_percentage' => 100,
            'status' => 'completed',
        ]);
    }
}
