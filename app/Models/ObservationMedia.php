<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Observation
 *
 * @property string $observation_id
 * @property string $tipe
 * @property string|null $disk
 * @property string|null $path
 * @property string $original_name
 * @property int|null $size
 * @property string $upload_status
 */
class ObservationMedia extends Model
{
    use HasUuids;

    protected $table = 'observation_media';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'observation_id',
        'tipe',
        'disk',
        'path',
        'original_name',
        'size',
        'checksum',
        'upload_status',
        'captured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Observation, $this>
     */
    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }

    public function isStored(): bool
    {
        return $this->upload_status === 'stored' && $this->path !== null;
    }
}
