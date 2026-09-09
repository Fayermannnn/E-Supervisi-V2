<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Observation\Enums\ObservationStatus;
use App\Domain\Observation\Enums\ObservationType;
use Database\Factories\ObservationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Observation
 *
 * @property string $id
 * @property string $cycle_id
 * @property string $observer_id
 * @property string $instrument_version_id
 * @property ObservationType $tipe
 * @property string|null $catatan_skrip
 * @property ObservationStatus $status
 * @property int $version
 * @property string|null $device_id
 * @property \Illuminate\Support\Carbon|null $mulai_at
 * @property \Illuminate\Support\Carbon|null $selesai_at
 * @property \Illuminate\Support\Carbon|null $finalized_at
 * @property \Illuminate\Support\Carbon|null $client_updated_at
 */
class Observation extends Model
{
    /** @use HasFactory<ObservationFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    // ID di-generate klien (UUID) untuk idempotensi sinkron luring.
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'cycle_id',
        'observer_id',
        'instrument_version_id',
        'tipe',
        'mulai_at',
        'selesai_at',
        'catatan_skrip',
        'status',
        'version',
        'finalized_at',
        'client_updated_at',
        'device_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe' => ObservationType::class,
            'status' => ObservationStatus::class,
            'version' => 'integer',
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
            'finalized_at' => 'datetime',
            'client_updated_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
    ];

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

    /**
     * @return HasMany<ObservationResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ObservationResponse::class);
    }

    /**
     * @return HasMany<ObservationMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ObservationMedia::class);
    }

    public function isFinal(): bool
    {
        return $this->status === ObservationStatus::Final;
    }
}
