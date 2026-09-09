<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Enums\CycleStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Append-only (docs/state-machine.md). Tidak ada updated_at; update/delete diblokir.
 *
 * @domain Supervision
 *
 * @property string $cycle_id
 * @property CycleStatus $from_status
 * @property CycleStatus $to_status
 * @property string|null $actor_id
 * @property string|null $actor_role
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 */
class CycleStatusTransition extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id',
        'from_status',
        'to_status',
        'actor_id',
        'actor_role',
        'reason',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => CycleStatus::class,
            'to_status' => CycleStatus::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Cycle transitions are append-only.'));
        static::deleting(fn () => throw new RuntimeException('Cycle transitions are append-only.'));
    }

    /**
     * @return BelongsTo<SupervisionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SupervisionCycle::class, 'cycle_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
