<?php

namespace App\Services\Document\Parsers;

use App\Services\Document\Contracts\DocumentParserInterface;
use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class DocxDocumentParser implements DocumentParserInterface
{
    /**
     * @return array{
     *     content: string,
     *     page_count: int|null,
     *     word_count: int,
     *     character_count: int,
     *     metadata: array<string, mixed>
     * }
     */
    public function parse(string $absolutePath): array
    {
        if (! file_exists($absolutePath)) {
            throw new RuntimeException("DOCX file not found: {$absolutePath}");
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($absolutePath);

        if ($openResult !== true) {
            throw new RuntimeException("Failed to open DOCX archive (Error code: {$openResult})");
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xmlContent === false || trim($xmlContent) === '') {
            throw new RuntimeException('DOCX archive missing word/document.xml or is empty');
        }

        $dom = new DOMDocument;
        $prevEntityLoader = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xmlContent, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prevEntityLoader);

        if (! $loaded) {
            throw new RuntimeException('Failed to parse XML content of DOCX document');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = [];
        $headings = [];
        $pageBreakCount = 1;

        $pNodes = $xpath->query('//w:p');

        if ($pNodes) {
            foreach ($pNodes as $index => $pNode) {
                // Check for page break inside paragraph
                $pageBreaks = $xpath->query('.//w:br[@w:type="page"] | .//w:lastRenderedPageBreak', $pNode);
                if ($pageBreaks && $pageBreaks->length > 0) {
                    $pageBreakCount += $pageBreaks->length;
                }

                // Check for heading style
                $styleNode = $xpath->query('.//w:pPr/w:pStyle/@w:val', $pNode);
                $style = ($styleNode && $styleNode->length > 0) ? $styleNode->item(0)->nodeValue : null;

                // Extract text nodes
                $textNodes = $xpath->query('.//w:t', $pNode);
                $pText = '';
                if ($textNodes) {
                    foreach ($textNodes as $tNode) {
                        $pText .= $tNode->nodeValue;
                    }
                }

                $pText = trim($pText);
                if ($pText !== '') {
                    $paragraphs[] = [
                        'index' => $index + 1,
                        'text' => $pText,
                        'style' => $style,
                        'estimated_page' => $pageBreakCount,
                    ];

                    if ($style && preg_match('/heading|title/i', $style)) {
                        $headings[] = [
                            'text' => $pText,
                            'style' => $style,
                            'paragraph_index' => $index + 1,
                        ];
                    }
                }
            }
        }

        $fullText = implode("\n\n", array_column($paragraphs, 'text'));
        $characterCount = mb_strlen($fullText);
        $wordCount = str_word_count(strip_tags($fullText));

        return [
            'content' => $fullText,
            'page_count' => max(1, $pageBreakCount),
            'word_count' => $wordCount,
            'character_count' => $characterCount,
            'metadata' => [
                'paragraphs' => $paragraphs,
                'headings' => $headings,
                'parser' => 'DocxDocumentParser',
            ],
        ];
    }
}
