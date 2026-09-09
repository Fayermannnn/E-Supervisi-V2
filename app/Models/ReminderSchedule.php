<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @domain Notification
 *
 * @property string $kind
 * @property string $channel
 * @property string $status
 * @property int $escalation_level
 * @property string|null $remindable_type
 * @property string|null $remindable_id
 * @property array<string, mixed>|null $payload
 * @property \Illuminate\Support\Carbon $send_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 */
class ReminderSchedule extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'remindable_type',
        'remindable_id',
        'user_id',
        'kind',
        'channel',
        'send_at',
        'status',
        'escalation_level',
        'sent_at',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'send_at' => 'datetime',
            'sent_at' => 'datetime',
            'escalation_level' => 'integer',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }
}
