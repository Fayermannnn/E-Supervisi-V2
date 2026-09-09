<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pola EAV (Spec §7). Satu baris per item instrumen yang dijawab.
 *
 * @domain Observation
 *
 * @property string $observation_id
 * @property string $instrument_version_id
 * @property string $section_key
 * @property string $item_key
 * @property float|null $value_numeric
 * @property bool|null $value_boolean
 * @property string|null $value_text
 * @property array<int|string, mixed>|null $value_json
 * @property string|null $catatan_item
 */
class ObservationResponse extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'observation_id',
        'instrument_version_id',
        'section_key',
        'item_key',
        'value_numeric',
        'value_boolean',
        'value_text',
        'value_json',
        'catatan_item',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_numeric' => 'float',
            'value_boolean' => 'boolean',
            'value_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Observation, $this>
     */
    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }
}
