<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slide extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slidebook_id',
        'title',
        'subtitle',
        'content',
        'summary',
        'layout',
        'order',
        'source_reference',
        'needs_review',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'source_reference' => 'array',
            'needs_review' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Slidebook, $this>
     */
    public function slidebook(): BelongsTo
    {
        return $this->belongsTo(Slidebook::class, 'slidebook_id');
    }
}
