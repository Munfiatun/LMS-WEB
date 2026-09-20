<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIProcessingResult extends Model
{
    use HasFactory;

    protected $table = 'ai_processing_results';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'log_id',
        'raw_response',
        'parsed_data',
        'tokens_used',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'tokens_used' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AIProcessingLog, $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(AIProcessingLog::class, 'log_id');
    }
}
