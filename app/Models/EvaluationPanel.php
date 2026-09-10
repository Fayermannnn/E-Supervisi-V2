<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain Evaluation
 *
 * Panel evaluasi ahli atas artefak sistem (DSR Artikel 3, Fase 5).
 *
 * @property string $id
 * @property string $judul
 * @property string|null $deskripsi
 * @property string $artefak_versi
 * @property string $status
 * @property string $dibuat_oleh
 * @property array<string, mixed>|null $stats
 * @property \Illuminate\Support\Carbon|null $closed_at
 */
class EvaluationPanel extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    /**
     * @var list<string>
     */
    protected $fillable = ['judul', 'deskripsi', 'artefak_versi', 'status', 'dibuat_oleh', 'stats', 'closed_at'];

    protected $attributes = ['status' => self::STATUS_DRAFT];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['stats' => 'array', 'closed_at' => 'datetime'];
    }

    /**
     * @return HasMany<PanelExpert, $this>
     */
    public function experts(): HasMany
    {
        return $this->hasMany(PanelExpert::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
