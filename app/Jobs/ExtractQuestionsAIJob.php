<?php

namespace App\Jobs;

use App\Models\QuestionBank;
use App\Services\AI\AIQuestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExtractQuestionsAIJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public QuestionBank $bank,
        public string $documentText
    ) {}

    public function handle(AIQuestionService $questionService): void
    {
        $questionService->extractQuestions($this->bank, $this->documentText);
    }

    public function failed(?Throwable $exception): void
    {
        // Failure is audited in AIProcessingLog
    }
}
