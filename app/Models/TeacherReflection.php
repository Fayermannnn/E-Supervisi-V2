<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Planning
 *
 * @property string $cycle_id
 * @property string $guru_id
 * @property string $tahap
 * @property string $konten
 * @property \Illuminate\Support\Carbon|null $submitted_at
 */
class TeacherReflection extends Model
{
    use HasUuids;

    public const TAHAP_PRA = 'pra_observasi';

    public const TAHAP_PASCA = 'pasca_umpan_balik';

    /**
     * @var list<string>
     */
    protected $fillable = ['cycle_id', 'guru_id', 'tahap', 'konten', 'submitted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<SupervisionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SupervisionCycle::class, 'cycle_id');
    }
}
