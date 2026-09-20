<?php

namespace App\Services\Document\Contracts;

interface DocumentParserInterface
{
    /**
     * Parse document and return extracted content and metadata.
     *
     * @return array{
     *     content: string,
     *     page_count: int|null,
     *     word_count: int,
     *     character_count: int,
     *     metadata: array<string, mixed>
     * }
     */
    public function parse(string $absolutePath): array;
}
