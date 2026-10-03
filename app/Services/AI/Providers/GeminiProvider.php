<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $model;

    protected int $timeout;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.gemini.api_key');
        $this->model = (string) config('ai.providers.gemini.model', 'gemini-1.5-flash');
        $this->timeout = (int) config('ai.providers.gemini.timeout', 60);
        $this->baseUrl = rtrim((string) config('ai.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
    }

    public function generateStructuredData(string $systemPrompt, string $userContent, array $schemaDefinition, ?int $maxOutputTokens = null): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is missing. Set GEMINI_API_KEY in .env or switch AI_PROVIDER to mock.');
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $combinedPrompt = $systemPrompt."\n\n=== SOURCE DOCUMENT CONTENT ===\n".$userContent;

        $response = Http::timeout($this->timeout)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $combinedPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                ...(($schemaDefinition['title'] ?? null) === 'quiz_generation' ? ['responseJsonSchema' => $schemaDefinition] : []),
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Gemini API error: HTTP {$response->status()} - {$response->body()}");
        }

        $responseData = $response->json();
        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokensUsed = $responseData['usageMetadata']['totalTokenCount'] ?? null;

        $parsed = json_decode($rawText, true);
        if (! is_array($parsed)) {
            throw new RuntimeException('Gemini response could not be parsed as valid JSON');
        }

        return [
            'raw_response' => $rawText,
            'parsed_data' => $parsed,
            'tokens_used' => $tokensUsed,
        ];
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
