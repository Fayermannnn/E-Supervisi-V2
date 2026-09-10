<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @domain Evaluation
 *
 * @property string $id
 * @property string $evaluation_panel_id
 * @property string $user_id
 * @property string $rumpun
 * @property string|null $afiliasi
 * @property \Illuminate\Support\Carbon|null $diundang_at
 */
class PanelExpert extends Model
{
    use HasUuids;

    public const RUMPUN_MANAJEMEN = 'manajemen_pendidikan';

    public const RUMPUN_SISTEM_INFORMASI = 'sistem_informasi';

    public const RUMPUN_LAINNYA = 'lainnya';

    /**
     * @var list<string>
     */
    protected $fillable = ['evaluation_panel_id', 'user_id', 'rumpun', 'afiliasi', 'diundang_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['diundang_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<EvaluationPanel, $this>
     */
    public function panel(): BelongsTo
    {
        return $this->belongsTo(EvaluationPanel::class, 'evaluation_panel_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasOne<ExpertReview, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(ExpertReview::class);
    }
}
