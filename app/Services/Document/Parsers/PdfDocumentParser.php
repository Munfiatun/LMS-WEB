<?php

namespace App\Services\Document\Parsers;

use App\Services\Document\Contracts\DocumentParserInterface;
use ErrorException;
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

        if (preg_match('/\/Encrypt\b/', $rawContent)) {
            throw new RuntimeException('Encrypted PDF is not supported');
        }

        $extractedText = '';
        preg_match_all('/\d+\s+\d+\s+obj\s*<<((?:(?!endobj|obj).)*?)>>\s*stream\r?\n(.*?)\r?\nendstream/s', $rawContent, $streams, PREG_SET_ORDER);
        foreach ($streams as $stream) {
            $dictionary = $stream[1];
            if (preg_match('/\/Subtype\s*\/Image\b/', $dictionary)) {
                continue;
            }
            $data = $stream[2];
            if (preg_match('/\/Filter\s*(\[[^\]]*\]|\/[A-Za-z0-9]+)/', $dictionary, $filter)) {
                $filterName = trim($filter[1], "[] \t\r\n");
                if ($filterName !== '/FlateDecode') {
                    throw new RuntimeException('Unsupported PDF stream filter');
                }
                $data = $this->decompressStream($data);
            }
            $text = $this->extractTextFromStream($data);
            if ($text !== '') {
                $extractedText .= $text."\n\n";
            }
        }

        $cleanedContent = $this->cleanExtractedText($extractedText);
        if ($cleanedContent === '') {
            throw new RuntimeException('PDF contains no supported extractable text');
        }
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

    private function decompressStream(string $data): string
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException('Invalid compressed PDF stream', 0, $severity);
        }, E_WARNING);
        try {
            $decoded = gzuncompress($data, 20 * 1024 * 1024);
            if ($decoded === false) {
                throw new RuntimeException('Invalid compressed PDF stream');
            }

            return $decoded;
        } catch (ErrorException $exception) {
            throw new RuntimeException('Invalid compressed PDF stream', previous: $exception);
        } finally {
            restore_error_handler();
        }
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
