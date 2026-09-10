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
 * @provisional
 *
 * @property string $id
 * @property string $calibration_session_id
 * @property string $supervisor_id
 * @property \Illuminate\Support\Carbon|null $submitted_at
 */
class CalibrationParticipant extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = ['calibration_session_id', 'supervisor_id', 'submitted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<CalibrationSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CalibrationSession::class, 'calibration_session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * @return HasMany<CalibrationScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(CalibrationScore::class);
    }

    public function hasSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
