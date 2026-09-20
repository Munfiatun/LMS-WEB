<?php

namespace App\Jobs;

use App\Models\LearningMaterial;
use App\Models\User;
use App\Services\AI\AISlidebookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateSlidebookAIJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public LearningMaterial $material,
        public User $creator,
        public bool $forceRegenerate = false
    ) {}

    public function handle(AISlidebookService $slidebookService): void
    {
        $slidebookService->generateSlidebookForMaterial(
            $this->material,
            $this->creator,
            $this->forceRegenerate
        );
    }

    public function failed(?Throwable $exception): void
    {
        // Failure is audited in AIProcessingLog
    }
}
