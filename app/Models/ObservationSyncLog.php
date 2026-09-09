<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak sinkronisasi observasi luring (ADR-006). Append-only.
 *
 * @domain Observation
 *
 * @property string $observation_id
 * @property string|null $device_id
 * @property string $action
 * @property int|null $client_version
 * @property int|null $server_version
 * @property bool $resolved
 */
class ObservationSyncLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'observation_sync_log';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'observation_id',
        'device_id',
        'action',
        'client_version',
        'server_version',
        'resolved',
        'payload_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_version' => 'integer',
            'server_version' => 'integer',
            'resolved' => 'boolean',
            'created_at' => 'datetime',
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
