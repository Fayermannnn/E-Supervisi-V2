<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Feedback
 *
 * @property string $feedback_session_id
 * @property string $poin_kesepakatan
 * @property bool $disepakati_kedua_pihak
 * @property int $urutan
 */
class FeedbackAgreement extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = ['feedback_session_id', 'poin_kesepakatan', 'disepakati_kedua_pihak', 'urutan'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['disepakati_kedua_pihak' => 'boolean', 'urutan' => 'integer'];
    }

    /**
     * @return BelongsTo<FeedbackSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(FeedbackSession::class, 'feedback_session_id');
    }
}
