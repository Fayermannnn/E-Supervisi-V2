<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Evaluation
 *
 * @property string $id
 * @property string $panel_expert_id
 * @property string $status
 * @property array<string, mixed> $jawaban
 * @property string|null $catatan
 * @property \Illuminate\Support\Carbon|null $submitted_at
 */
class ExpertReview extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_TERKIRIM = 'terkirim';

    /**
     * @var list<string>
     */
    protected $fillable = ['panel_expert_id', 'status', 'jawaban', 'catatan', 'submitted_at'];

    protected $attributes = ['status' => self::STATUS_DRAFT];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['jawaban' => 'array', 'submitted_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<PanelExpert, $this>
     */
    public function panelExpert(): BelongsTo
    {
        return $this->belongsTo(PanelExpert::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_TERKIRIM;
    }
}
