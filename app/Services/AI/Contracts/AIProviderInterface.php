<?php

namespace App\Services\AI\Contracts;

interface AIProviderInterface
{
    /**
     * Generate structured JSON data adhering to schema definition.
     *
     * @param  array<string, mixed>  $schemaDefinition
     * @return array{
     *     raw_response: string,
     *     parsed_data: array<string, mixed>,
     *     tokens_used: int|null
     * }
     */
    public function generateStructuredData(string $systemPrompt, string $userContent, array $schemaDefinition): array;

    public function getProviderName(): string;

    public function getModelName(): string;
}
