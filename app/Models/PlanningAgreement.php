<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Observation\Enums\ObservationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Planning
 *
 * @property string $cycle_id
 * @property string $fokus_observasi
 * @property string|null $tujuan
 * @property string $instrument_id
 * @property string $instrument_version_id
 * @property ObservationType $tipe_observasi
 * @property \Illuminate\Support\Carbon $jadwal_mulai
 * @property \Illuminate\Support\Carbon|null $jadwal_selesai
 * @property string|null $kelas
 * @property string|null $mata_pelajaran
 * @property \Illuminate\Support\Carbon|null $disepakati_guru_at
 * @property \Illuminate\Support\Carbon|null $disepakati_supervisor_at
 */
class PlanningAgreement extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id',
        'fokus_observasi',
        'tujuan',
        'instrument_id',
        'instrument_version_id',
        'tipe_observasi',
        'jadwal_mulai',
        'jadwal_selesai',
        'lokasi',
        'kelas',
        'mata_pelajaran',
        'disepakati_guru_at',
        'disepakati_supervisor_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe_observasi' => ObservationType::class,
            'jadwal_mulai' => 'datetime',
            'jadwal_selesai' => 'datetime',
            'disepakati_guru_at' => 'datetime',
            'disepakati_supervisor_at' => 'datetime',
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
     * @return BelongsTo<InstrumentVersion, $this>
     */
    public function instrumentVersion(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class);
    }

    public function isFullyAgreed(): bool
    {
        return $this->disepakati_guru_at !== null && $this->disepakati_supervisor_at !== null;
    }
}
