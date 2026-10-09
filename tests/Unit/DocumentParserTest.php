<?php

namespace Tests\Unit;

use App\Services\Document\Parsers\DocxDocumentParser;
use App\Services\Document\Parsers\PdfDocumentParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

class DocumentParserTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/lms_parser_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Cleanup temp files
        $files = glob($this->tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_docx_parser_extracts_paragraphs_and_headings(): void
    {
        $docxPath = $this->tempDir.'/sample.docx';

        $zip = new ZipArchive;
        $zip->open($docxPath, ZipArchive::CREATE);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>
                <w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:t>Pengantar Pemrograman Berorientasi Objek</w:t></w:p>
                <w:p><w:t>Konsep enkapsulasi dan polimorfisme adalah dua pilar mendasar.</w:t></w:p>
            </w:body>
        </w:document>';
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $parser = new DocxDocumentParser;
        $result = $parser->parse($docxPath);

        $this->assertNotEmpty($result['content']);
        $this->assertStringContainsString('Pengantar Pemrograman Berorientasi Objek', $result['content']);
        $this->assertStringContainsString('Konsep enkapsulasi', $result['content']);
        $this->assertGreaterThan(0, $result['word_count']);
        $this->assertCount(1, $result['metadata']['headings']);
    }

    public function test_docx_parser_rejects_external_entities_without_loading_them(): void
    {
        $docxPath = $this->tempDir.'/external.docx';
        $secretPath = $this->tempDir.'/private.txt';
        file_put_contents($secretPath, 'private sentinel');
        $xml = '<?xml version="1.0"?><!DOCTYPE document [<!ENTITY secret SYSTEM "file://'.$secretPath.'">]>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body><w:p><w:t>&secret;</w:t></w:p></w:body></w:document>';
        $zip = new ZipArchive;
        $zip->open($docxPath, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
        $externalLoads = 0;
        libxml_set_external_entity_loader(function () use (&$externalLoads) {
            $externalLoads++;

            return null;
        });

        try {
            (new DocxDocumentParser)->parse($docxPath);
            $this->fail('A DOCX with a DTD must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Failed to parse XML content of DOCX document', $exception->getMessage());
            $this->assertSame(0, $externalLoads);
        } finally {
            libxml_set_external_entity_loader(null);
        }
    }

    public function test_docx_parser_does_not_resolve_xinclude(): void
    {
        $docxPath = $this->tempDir.'/include.docx';
        $secretPath = $this->tempDir.'/private.txt';
        file_put_contents($secretPath, 'private sentinel');
        $zip = new ZipArchive;
        $zip->open($docxPath, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:xi="http://www.w3.org/2001/XInclude">'
            .'<w:body><w:p><w:t>Normal text</w:t><xi:include href="file://'.$secretPath.'" parse="text"/></w:p></w:body></w:document>');
        $zip->close();

        $result = (new DocxDocumentParser)->parse($docxPath);

        $this->assertSame('Normal text', $result['content']);
    }

    public function test_docx_parser_throws_exception_on_corrupt_file(): void
    {
        $corruptPath = $this->tempDir.'/corrupt.docx';
        file_put_contents($corruptPath, 'not a valid zip file content');

        $this->expectException(RuntimeException::class);

        $parser = new DocxDocumentParser;
        $parser->parse($corruptPath);
    }

    public function test_pdf_parser_extracts_stream_text_and_counts_pages(): void
    {
        $pdfPath = $this->tempDir.'/sample.pdf';
        $pdfContent = "%PDF-1.4\n"
            ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >> endobj\n"
            ."4 0 obj << /Length 60 >> stream\n"
            ."BT\n/F1 12 Tf\n(Pengantar Algoritma dan Pemrograman Komputer) Tj\nET\n"
            ."endstream\nendobj\n"
            ."xref\n0 5\n0000000000 65535 f \n"
            ."trailer << /Size 5 /Root 1 0 R >>\n"
            ."startxref\n300\n%%EOF";

        file_put_contents($pdfPath, $pdfContent);

        $parser = new PdfDocumentParser;
        $result = $parser->parse($pdfPath);

        $this->assertStringContainsString('Pengantar Algoritma dan Pemrograman Komputer', $result['content']);
        $this->assertEquals(1, $result['page_count']);
        $this->assertGreaterThan(0, $result['word_count']);
    }

    public function test_pdf_parser_throws_exception_on_invalid_pdf_header(): void
    {
        $invalidPath = $this->tempDir.'/invalid.pdf';
        file_put_contents($invalidPath, 'This is plain text, not a PDF');

        $this->expectException(RuntimeException::class);

        $parser = new PdfDocumentParser;
        $parser->parse($invalidPath);
    }
}
