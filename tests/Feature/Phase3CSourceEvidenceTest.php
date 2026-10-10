<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\AI\AIContentService;
use App\Services\AI\AIQuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class Phase3CSourceEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_quiz_persists_application_derived_slide_evidence_and_shows_it_to_teacher(): void
    {
        config(['ai.provider' => 'openai']);

        $teacher = $this->teacher();
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'content' => 'Konten material di luar Slidebook tidak boleh menjadi sumber quiz AI.',
        ]);
        $slidebook = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Tata Surya',
            'created_by' => $teacher->id,
            'status' => 'published',
        ]);
        $slidebook->slides()->createMany([
            [
                'title' => 'Planet Dalam',
                'content' => '<p>Merkurius adalah planet yang paling dekat dengan Matahari.</p>',
                'summary' => 'Merkurius berada paling dekat dengan Matahari.',
                'order' => 1,
            ],
            [
                'title' => 'Orbit Bumi',
                'content' => '<p>Bumi mengelilingi Matahari dalam orbitnya.</p>',
                'summary' => 'Bumi mengorbit Matahari.',
                'order' => 2,
            ],
        ]);

        $ai = Mockery::mock(AIContentService::class);
        $ai->shouldReceive('process')->once()->andReturn([
            'parsed_data' => [
                'questions' => [
                    [
                        'question_text' => 'Planet apa yang paling dekat dengan Matahari?',
                        'options' => ['Venus', 'Merkurius', 'Bumi', 'Mars'],
                        'correct_option' => 1,
                        'difficulty' => 'easy',
                        'explanation' => 'Merkurius adalah planet yang paling dekat dengan Matahari.',
                        'source_slide_number' => 1,
                    ],
                    [
                        'question_text' => 'Apa yang dikelilingi Bumi?',
                        'options' => ['Matahari', 'Mars', 'Venus', 'Merkurius'],
                        'correct_option' => 0,
                        'difficulty' => 'easy',
                        'explanation' => 'Bumi mengelilingi Matahari.',
                        'source_slide_number' => 2,
                    ],
                ],
            ],
        ]);

        $quiz = (new AIQuizService($ai))->generate($slidebook, $teacher, [
            'total_questions' => 2,
            'difficulty' => 'easy',
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'custom_instructions' => 'Buat dua soal pemahaman dasar dari materi.',
        ]);

        $question = $quiz->questions()->orderBy('order')->firstOrFail();
        $this->assertSame(1, $question->source_slide_number);
        $this->assertStringContainsString('Merkurius adalah planet yang paling dekat dengan Matahari.', $question->source_excerpt);
        $this->assertStringNotContainsString('Konten material di luar Slidebook', $question->source_excerpt);

        $this->actingAs($teacher)
            ->get(route('instructor.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('Referensi Materi')
            ->assertSee('Slide 1')
            ->assertSee('Merkurius adalah planet yang paling dekat dengan Matahari.');
    }

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
