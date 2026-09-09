<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Instruments\InstrumentSchema;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Instruments
 *
 * @property string $id
 * @property string $instrument_id
 * @property int $version
 * @property array<string, mixed> $schema_json
 * @property array<string, mixed>|null $scoring_config
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class InstrumentVersion extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'instrument_id',
        'version',
        'schema_json',
        'scoring_config',
        'catatan_perubahan',
        'published_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'scoring_config' => 'array',
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Instrument, $this>
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function schema(): InstrumentSchema
    {
        return InstrumentSchema::fromArray($this->schema_json);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
