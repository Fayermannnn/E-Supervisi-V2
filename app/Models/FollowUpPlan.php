<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain FollowUp
 *
 * @property string $cycle_id
 * @property string $tujuan
 * @property string $dibuat_oleh
 * @property \Illuminate\Support\Carbon $mulai
 * @property \Illuminate\Support\Carbon $tenggat
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $selesai_at
 */
class FollowUpPlan extends Model
{
    use HasUuids;

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_TERLAMBAT = 'terlambat';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    /**
     * @var list<string>
     */
    protected $fillable = ['cycle_id', 'tujuan', 'dibuat_oleh', 'mulai', 'tenggat', 'status', 'selesai_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'tenggat' => 'date',
            'selesai_at' => 'datetime',
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
     * @return HasMany<FollowUpItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(FollowUpItem::class)->orderBy('urutan');
    }

    /**
     * @param  Builder<FollowUpPlan>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_BERJALAN, self::STATUS_TERLAMBAT]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_BERJALAN, self::STATUS_TERLAMBAT], true);
    }
}
