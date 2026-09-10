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
 * @property string $pengirim_id
 * @property string $peran
 * @property string $tipe
 * @property string $konten
 * @property int $urutan
 * @property string|null $ai_generation_id
 */
class FeedbackMessage extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    public const TIPE_OBSERVASI = 'observasi';

    public const TIPE_PERTANYAAN = 'pertanyaan_reflektif';

    public const TIPE_TANGGAPAN = 'tanggapan';

    public const TIPE_KESEPAKATAN = 'kesepakatan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'feedback_session_id',
        'pengirim_id',
        'peran',
        'tipe',
        'konten',
        'urutan',
        'ai_generation_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['urutan' => 'integer', 'created_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    /**
     * @return BelongsTo<AiGeneration, $this>
     */
    public function aiGeneration(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class);
    }
}
