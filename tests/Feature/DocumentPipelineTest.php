<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\MaterialDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Document\Parsers\PdfDocumentParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DocumentPipelineTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('invalidPdfs')]
    public function test_corrupt_or_unsupported_pdf_throws_controlled_exception_without_warning(string $filter, string $content): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('invalid.pdf', $this->pdf($filter, $content));
        $warnings = [];
        set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });
        try {
            (new PdfDocumentParser)->parse(Storage::disk('private')->path('invalid.pdf'));
            $this->fail('Unsupported PDF must not yield invented content.');
        } catch (RuntimeException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $warnings);
    }

    public static function invalidPdfs(): array
    {
        return [
            'corrupt flate' => ['/Filter /FlateDecode', 'BT (Should not be used as fallback) Tj ET'],
            'unsupported filter' => ['/Filter /LZWDecode', 'BT (Not supported) Tj ET'],
            'no extractable text' => ['', 'no text operators'],
            'multiple filters' => ['/Filter [/ASCII85Decode /FlateDecode]', 'invalid'],
        ];
    }

    public function test_valid_compressed_and_uncompressed_pdf_extract_real_text(): void
    {
        Storage::fake('private');
        $text = 'BT (Valid PDF content) Tj ET';
        Storage::disk('private')->put('compressed.pdf', $this->pdf('/Filter /FlateDecode', gzcompress($text)));
        Storage::disk('private')->put('plain.pdf', $this->pdf('', $text));

        $compressed = (new PdfDocumentParser)->parse(Storage::disk('private')->path('compressed.pdf'));
        $plain = (new PdfDocumentParser)->parse(Storage::disk('private')->path('plain.pdf'));

        $this->assertSame('Valid PDF content', $compressed['content']);
        $this->assertSame('Valid PDF content', $plain['content']);
    }

    public function test_failed_upload_has_failed_state_safe_feedback_and_blocks_material_publish(): void
    {
        [$teacher, $material] = $this->material();
        Log::spy();
        $file = UploadedFile::fake()->createWithContent('broken.pdf', $this->pdf('/Filter /FlateDecode', 'invalid compressed content'));

        $this->actingAs($teacher)->post(route('instructor.materials.documents.store', $material), ['document' => $file])
            ->assertSessionHas('error', 'Dokumen gagal diproses. Gunakan PDF teks atau DOCX yang valid.');

        $document = MaterialDocument::sole();
        $this->assertSame('failed', $document->extraction->status);
        $this->assertSame('', $document->extraction->content);
        $this->assertSame('draft', $material->fresh()->status);
        $this->postJson(route('instructor.materials.publish', $material))->assertUnprocessable()->assertJsonValidationErrors('material');
        $this->get(route('instructor.materials.edit', $material))->assertOk()->assertSee('Failed')->assertDontSee('gzuncompress')->assertDontSee('Publikasikan Materi');
        Log::shouldHaveReceived('error')->once()->with('Document extraction failed', [
            'document_id' => $document->id, 'exception_type' => RuntimeException::class, 'exception_code' => 0,
        ]);
    }

    public function test_material_with_valid_document_can_publish_without_slidebook_or_text(): void
    {
        [$teacher, $material] = $this->material();
        $material->update(['content' => null]);
        $file = UploadedFile::fake()->createWithContent('lesson.pdf', $this->pdf('', 'BT (Lesson from document) Tj ET'));
        $this->actingAs($teacher)->post(route('instructor.materials.documents.store', $material), ['document' => $file])->assertSessionHas('success');
        $this->assertSame('completed', MaterialDocument::sole()->extraction->status);
        $this->post(route('instructor.materials.publish', $material))->assertSessionHas('success');
        $this->assertSame('published', $material->fresh()->status);
        $this->assertDatabaseCount('slidebooks', 0);
    }

    public function test_published_material_document_cannot_be_replaced_through_upload(): void
    {
        [$teacher, $material] = $this->material();
        $material->update(['status' => 'published']);
        $file = UploadedFile::fake()->createWithContent('replacement.pdf', $this->pdf('', 'BT (Replacement) Tj ET'));
        $this->actingAs($teacher)->postJson(route('instructor.materials.documents.store', $material), ['document' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors('material');
        $this->assertDatabaseCount('material_documents', 0);
    }

    /** @return array{User, LearningMaterial} */
    private function material(): array
    {
        Storage::fake('private');
        $role = Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id, 'status' => 'draft']);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id, 'status' => 'draft']);

        return [$teacher, $material];
    }

    private function pdf(string $filter, string $content): string
    {
        return "%PDF-1.4\n1 0 obj << /Length ".strlen($content)." {$filter} >> stream\n{$content}\nendstream\nendobj\n%%EOF";
    }
}
