<?php

namespace Tests\Unit;

use App\Models\AIProcessingLog;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AIContentService;
use App\Services\AI\Providers\MockAIProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIContentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $this->instructor = User::factory()->create(['role_id' => $role->id]);

        $category = Category::create(['name' => 'Teknologi', 'slug' => 'teknologi']);
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $category->id,
            'title' => 'Dasar PHP Modern',
            'slug' => 'dasar-php-modern',
            'status' => 'published',
        ]);

        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Bab 1',
            'order' => 1,
        ]);

        $this->material = LearningMaterial::create([
            'section_id' => $section->id,
            'title' => 'Variabel dan Tipe Data',
            'slug' => 'variabel-dan-tipe-data',
            'status' => 'draft',
            'order' => 1,
        ]);
    }

    public function test_mock_ai_provider_generates_schema_compliant_slidebook_structure(): void
    {
        $provider = new MockAIProvider;
        $content = "Pengantar Arsitektur Perangkat Lunak.\n\nPrinsip arsitektur yang baik memisahkan presentation layer dan domain layer.";

        $result = $provider->generateStructuredData('system prompt', $content, []);

        $this->assertIsArray($result['parsed_data']);
        $this->assertArrayHasKey('title', $result['parsed_data']);
        $this->assertArrayHasKey('slides', $result['parsed_data']);
        $this->assertNotEmpty($result['parsed_data']['slides']);

        $firstSlide = $result['parsed_data']['slides'][0];
        $this->assertArrayHasKey('title', $firstSlide);
        $this->assertArrayHasKey('content', $firstSlide);
        $this->assertArrayHasKey('source_reference', $firstSlide);
        $this->assertArrayHasKey('needs_review', $firstSlide);
    }

    public function test_ai_content_service_records_logs_and_results_in_database(): void
    {
        $service = new AIContentService;
        $sampleText = "Algoritma Pemrograman Lanjut.\n\nPembahasan kompleksitas waktu dan ruang pada sorting.";

        $output = $service->process(
            processType: AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            sourceModel: $this->material,
            systemPrompt: 'System prompt testing',
            userContent: $sampleText,
            schemaDefinition: [],
            providerOverride: 'mock'
        );

        $this->assertDatabaseHas('ai_processing_logs', [
            'process_type' => AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            'source_type' => LearningMaterial::class,
            'source_id' => $this->material->id,
            'provider' => 'mock',
            'status' => AIProcessingLog::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('ai_processing_results', [
            'log_id' => $output['log']->id,
        ]);
    }

    public function test_ai_content_service_idempotency_returns_existing_result(): void
    {
        $service = new AIContentService;
        $sampleText = 'Idempotency Test Document Content.';

        // First execution
        $firstOutput = $service->process(
            processType: AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            sourceModel: $this->material,
            systemPrompt: 'Prompt',
            userContent: $sampleText,
            schemaDefinition: [],
            providerOverride: 'mock'
        );

        // Second execution with identical input
        $secondOutput = $service->process(
            processType: AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            sourceModel: $this->material,
            systemPrompt: 'Prompt',
            userContent: $sampleText,
            schemaDefinition: [],
            providerOverride: 'mock'
        );

        // Should return same log id (idempotency)
        $this->assertEquals($firstOutput['log']->id, $secondOutput['log']->id);
        $this->assertEquals(1, AIProcessingLog::where('source_id', $this->material->id)->count());
    }
}
