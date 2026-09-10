<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain Feedback
 *
 * @property string $cycle_id
 * @property string $metode
 * @property string $status
 * @property string $status_konfirmasi_guru
 * @property \Illuminate\Support\Carbon|null $dijadwalkan_at
 * @property \Illuminate\Support\Carbon|null $dilaksanakan_at
 * @property \Illuminate\Support\Carbon|null $dikonfirmasi_guru_at
 */
class FeedbackSession extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_BERLANGSUNG = 'berlangsung';

    public const STATUS_SELESAI = 'selesai';

    public const KONFIRMASI_MENUNGGU = 'menunggu';

    public const KONFIRMASI_DIKONFIRMASI = 'dikonfirmasi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id',
        'dijadwalkan_at',
        'dilaksanakan_at',
        'metode',
        'status',
        'status_konfirmasi_guru',
        'dikonfirmasi_guru_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dijadwalkan_at' => 'datetime',
            'dilaksanakan_at' => 'datetime',
            'dikonfirmasi_guru_at' => 'datetime',
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
     * @return HasMany<FeedbackMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(FeedbackMessage::class)->orderBy('urutan');
    }

    /**
     * @return HasMany<FeedbackAgreement, $this>
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(FeedbackAgreement::class)->orderBy('urutan');
    }

    public function isConfirmed(): bool
    {
        return $this->status_konfirmasi_guru === self::KONFIRMASI_DIKONFIRMASI;
    }
}
