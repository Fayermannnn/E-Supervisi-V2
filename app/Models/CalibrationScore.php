<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Accountability
 *
 * @provisional
 *
 * @property string $id
 * @property string $calibration_session_id
 * @property string $calibration_participant_id
 * @property string|null $section_key
 * @property string $item_key
 * @property float $nilai
 */
class CalibrationScore extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'calibration_session_id', 'calibration_participant_id', 'section_key', 'item_key', 'nilai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['nilai' => 'float'];
    }

    /**
     * @return BelongsTo<CalibrationParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(CalibrationParticipant::class, 'calibration_participant_id');
    }
}
