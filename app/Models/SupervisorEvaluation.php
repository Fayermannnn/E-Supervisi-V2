<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Accountability
 *
 * @provisional
 *
 * Penilaian 360°: guru menilai PROSES supervisi yang diterimanya (bukan
 * performa mengajarnya). Respons individual tidak pernah diekspos — hanya
 * agregat di atas ambang anonimitas (policy accountability.min_responses).
 *
 * @property string $id
 * @property string $cycle_id
 * @property string $guru_id
 * @property string $supervisor_id
 * @property string $dinas_id
 * @property string|null $sekolah_id
 * @property array<string, int> $jawaban
 * @property string|null $komentar
 * @property \Illuminate\Support\Carbon $submitted_at
 */
class SupervisorEvaluation extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id', 'guru_id', 'supervisor_id', 'dinas_id', 'sekolah_id',
        'jawaban', 'komentar', 'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupervisionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SupervisionCycle::class, 'cycle_id');
    }
}
