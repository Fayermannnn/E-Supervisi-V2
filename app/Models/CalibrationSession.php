<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain Accountability
 *
 * @provisional Hanya relevan bila multi-supervisor. Uji konsistensi penilaian
 * antar pengawas/kepala sekolah — mendukung reliabilitas Artikel 2.
 *
 * @property string $id
 * @property string $dinas_id
 * @property string $instrument_version_id
 * @property string|null $observation_id
 * @property string $judul
 * @property string|null $deskripsi
 * @property string|null $artefak_url
 * @property string $status
 * @property string $dibuat_oleh
 * @property array<string, mixed>|null $stats
 * @property \Illuminate\Support\Carbon|null $closed_at
 */
class CalibrationSession extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dinas_id', 'instrument_version_id', 'observation_id', 'judul', 'deskripsi',
        'artefak_url', 'status', 'dibuat_oleh', 'stats', 'closed_at',
    ];

    protected $attributes = ['status' => self::STATUS_DRAFT];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InstrumentVersion, $this>
     */
    public function instrumentVersion(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class, 'instrument_version_id');
    }

    /**
     * @return HasMany<CalibrationParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(CalibrationParticipant::class);
    }

    /**
     * @return HasMany<CalibrationScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(CalibrationScore::class);
    }
}
