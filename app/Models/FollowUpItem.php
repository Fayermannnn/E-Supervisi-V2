<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain FollowUp
 *
 * @property string $follow_up_plan_id
 * @property string $deskripsi
 * @property string $indikator_keberhasilan
 * @property string $status
 * @property int $urutan
 * @property \Illuminate\Support\Carbon|null $tenggat_item
 * @property \Illuminate\Support\Carbon|null $selesai_at
 */
class FollowUpItem extends Model
{
    use HasUuids;

    public const STATUS_BELUM = 'belum';

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'follow_up_plan_id',
        'deskripsi',
        'indikator_keberhasilan',
        'tenggat_item',
        'status',
        'urutan',
        'selesai_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenggat_item' => 'date',
            'selesai_at' => 'datetime',
            'urutan' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<FollowUpPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(FollowUpPlan::class, 'follow_up_plan_id');
    }

    /**
     * @return HasMany<FollowUpEvidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(FollowUpEvidence::class);
    }
}
