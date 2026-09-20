<?php

namespace App\Services\Document\Parsers;

use App\Services\Document\Contracts\DocumentParserInterface;
use RuntimeException;

class PdfDocumentParser implements DocumentParserInterface
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
            throw new RuntimeException("PDF file not found: {$absolutePath}");
        }

        $rawContent = file_get_contents($absolutePath);

        if ($rawContent === false || strlen($rawContent) < 5) {
            throw new RuntimeException('Failed to read PDF file or file is empty');
        }

        if (! str_starts_with($rawContent, '%PDF-')) {
            throw new RuntimeException('Invalid PDF file format (missing %PDF- header)');
        }

        // Detect page count
        $pageCount = 1;
        if (preg_match_all('/\/Type\s*\/Page\b(?!\s*s)/i', $rawContent, $pageMatches)) {
            $pageCount = max(1, count($pageMatches[0]));
        } elseif (preg_match('/\/Count\s+(\d+)/', $rawContent, $countMatch)) {
            $pageCount = max(1, (int) $countMatch[1]);
        }

        // Extract text from stream objects
        $extractedText = '';
        $streamMatches = [];

        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $rawContent, $streamMatches);

        if (! empty($streamMatches[1])) {
            foreach ($streamMatches[1] as $streamData) {
                // Try decompressing FlateDecode
                $uncompressed = @gzuncompress($streamData);
                $dataToParse = ($uncompressed !== false) ? $uncompressed : $streamData;

                $text = $this->extractTextFromStream($dataToParse);
                if ($text !== '') {
                    $extractedText .= $text."\n\n";
                }
            }
        }

        // Fallback: If no stream text found, scan for direct Tj/TJ in raw text
        if (trim($extractedText) === '') {
            $fallback = $this->extractTextFromStream($rawContent);
            if (trim($fallback) !== '') {
                $extractedText = $fallback;
            }
        }

        $cleanedContent = $this->cleanExtractedText($extractedText);
        $characterCount = mb_strlen($cleanedContent);
        $wordCount = str_word_count(strip_tags($cleanedContent));

        return [
            'content' => $cleanedContent,
            'page_count' => $pageCount,
            'word_count' => $wordCount,
            'character_count' => $characterCount,
            'metadata' => [
                'page_count' => $pageCount,
                'parser' => 'PdfDocumentParser',
            ],
        ];
    }

    /**
     * Extract strings inside Tj and TJ operators within BT ... ET blocks.
     */
    protected function extractTextFromStream(string $stream): string
    {
        $text = '';

        // Match BT (begin text) ... ET (end text) blocks
        if (preg_match_all('/BT[\s\S]*?ET/s', $stream, $btBlocks)) {
            foreach ($btBlocks[0] as $block) {
                // Match (text) Tj
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $tjMatches)) {
                    foreach ($tjMatches[1] as $match) {
                        $text .= $this->unescapePdfString($match).' ';
                    }
                }

                // Match [(t1) 10 (t2)] TJ
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjArrayMatches)) {
                    foreach ($tjArrayMatches[1] as $arrayContent) {
                        if (preg_match_all('/\((.*?)\)/s', $arrayContent, $stringParts)) {
                            foreach ($stringParts[1] as $part) {
                                $text .= $this->unescapePdfString($part);
                            }
                            $text .= ' ';
                        }
                    }
                }
                $text .= "\n";
            }
        }

        return trim($text);
    }

    /**
     * Unescape standard PDF octal and escape sequences.
     */
    protected function unescapePdfString(string $str): string
    {
        $str = str_replace(
            ['\\\\', '\(', '\)', '\n', '\r', '\t', '\b', '\f'],
            ['\\', '(', ')', "\n", "\r", "\t", "\x08", "\x0C"],
            $str
        );

        // Replace octal escapes (\ddd)
        return preg_replace_callback('/\\\\([0-7]{1,3})/', function (array $m): string {
            return chr((int) octdec($m[1]));
        }, $str) ?? $str;
    }

    /**
     * Clean and normalize raw extracted text.
     */
    protected function cleanExtractedText(string $text): string
    {
        // Remove non-printable control characters except whitespace
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;

        // Normalize multiple line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
