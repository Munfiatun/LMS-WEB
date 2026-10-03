<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\AIProcessingResult;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\GroqProvider;
use App\Services\AI\Providers\MockAIProvider;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Throwable;

class AIContentService
{
    /**
     * Resolve the active AI provider instance based on configuration.
     */
    public function getProvider(?string $providerName = null): AIProviderInterface
    {
        $provider = $providerName ?? config('ai.provider', 'mock');

        return match ($provider) {
            'mock' => new MockAIProvider,
            'openai' => new OpenAIProvider,
            'gemini' => new GeminiProvider,
            'groq' => new GroqProvider,
            default => throw new InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }

    /**
     * Process content with AI, enforcing idempotency tracking, audit logging, and source fidelity.
     *
     * @param  array<string, mixed>  $schemaDefinition
     * @return array{
     *     log: AIProcessingLog,
     *     result: AIProcessingResult,
     *     parsed_data: array<string, mixed>
     * }
     */
    public function process(
        string $processType,
        Model $sourceModel,
        string $systemPrompt,
        string $userContent,
        array $schemaDefinition,
        ?string $providerOverride = null,
        ?int $maxOutputTokens = null
    ): array {
        $provider = $this->getProvider($providerOverride);
        $promptVersion = (string) config('ai.prompt_version', 'v1.0');
        $inputHash = hash('sha256', $promptVersion.':'.$userContent);

        // Check if an existing completed log with same hash exists (idempotency audit)
        $existingLog = AIProcessingLog::where('input_hash', $inputHash)
            ->where('prompt_version', $promptVersion)
            ->where('status', AIProcessingLog::STATUS_COMPLETED)
            ->where('source_type', get_class($sourceModel))
            ->where('source_id', $sourceModel->getKey())
            ->latest()
            ->first();

        if ($existingLog && $existingLog->result) {
            return [
                'log' => $existingLog,
                'result' => $existingLog->result,
                'parsed_data' => $existingLog->result->parsed_data,
            ];
        }

        // Create processing log
        $log = AIProcessingLog::create([
            'process_type' => $processType,
            'source_type' => get_class($sourceModel),
            'source_id' => $sourceModel->getKey(),
            'provider' => $provider->getProviderName(),
            'model' => $provider->getModelName(),
            'prompt_version' => $promptVersion,
            'input_hash' => $inputHash,
            'status' => AIProcessingLog::STATUS_PROCESSING,
            'attempt_count' => 1,
            'processing_started_at' => now(),
        ]);

        try {
            $generated = $provider->generateStructuredData($systemPrompt, $userContent, $schemaDefinition, $maxOutputTokens);

            $result = AIProcessingResult::create([
                'log_id' => $log->id,
                'raw_response' => $generated['raw_response'],
                'parsed_data' => $generated['parsed_data'],
                'tokens_used' => $generated['tokens_used'],
            ]);

            $log->update([
                'status' => AIProcessingLog::STATUS_COMPLETED,
                'processing_completed_at' => now(),
            ]);

            return [
                'log' => $log,
                'result' => $result,
                'parsed_data' => $generated['parsed_data'],
            ];
        } catch (Throwable $e) {
            $log->update([
                'status' => AIProcessingLog::STATUS_FAILED,
                'error_code' => (string) $e->getCode(),
                'error_message' => $e->getMessage(),
                'processing_completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
