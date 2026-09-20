<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $model;

    protected int $timeout;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.openai.api_key');
        $this->model = (string) config('ai.providers.openai.model', 'gpt-4o-mini');
        $this->timeout = (int) config('ai.providers.openai.timeout', 60);
        $this->baseUrl = rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');
    }

    public function generateStructuredData(string $systemPrompt, string $userContent, array $schemaDefinition): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API key is missing. Set OPENAI_API_KEY in .env or switch AI_PROVIDER to mock.');
        }

        $systemPromptWithJson = $systemPrompt."\nIMPORTANT: You must respond with pure, valid JSON strictly adhering to the requested schema. Do not enclose JSON in markdown code fences.";

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPromptWithJson],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("OpenAI API error: HTTP {$response->status()} - {$response->body()}");
        }

        $responseData = $response->json();
        $content = $responseData['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $responseData['usage']['total_tokens'] ?? null;

        $parsed = json_decode($content, true);
        if (! is_array($parsed)) {
            throw new RuntimeException('OpenAI response could not be decoded into a valid JSON array');
        }

        return [
            'raw_response' => $content,
            'parsed_data' => $parsed,
            'tokens_used' => $tokensUsed,
        ];
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
