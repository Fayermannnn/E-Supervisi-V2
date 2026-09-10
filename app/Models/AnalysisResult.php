<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain Analysis
 *
 * @property string $cycle_id
 * @property string|null $observation_id
 * @property array<string, mixed>|null $score_summary
 * @property string|null $ringkasan
 * @property string $sumber
 * @property string $status_review
 * @property string|null $reviewed_by
 * @property string|null $ai_generation_id
 * @property \Illuminate\Support\Carbon|null $finalized_at
 */
class AnalysisResult extends Model
{
    use HasUuids;

    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_AI_DRAFT = 'ai_draft';

    public const SUMBER_AI_EDITED = 'ai_edited';

    public const REVIEW_DRAFT = 'draft';

    public const REVIEW_IN_REVIEW = 'in_review';

    public const REVIEW_FINAL = 'final';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id',
        'observation_id',
        'score_summary',
        'ringkasan',
        'sumber',
        'status_review',
        'reviewed_by',
        'ai_generation_id',
        'finalized_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score_summary' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupervisionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SupervisionCycle::class, 'cycle_id');
    }

    /**
     * @return HasMany<AnalysisFinding, $this>
     */
    public function findings(): HasMany
    {
        return $this->hasMany(AnalysisFinding::class)->orderBy('urutan');
    }

    /**
     * @return BelongsTo<AiGeneration, $this>
     */
    public function aiGeneration(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class);
    }

    public function isFinal(): bool
    {
        return $this->status_review === self::REVIEW_FINAL;
    }
}
