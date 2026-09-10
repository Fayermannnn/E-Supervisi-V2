<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Program
 *
 * @property string $id
 * @property string $annual_program_id
 * @property string $guru_id
 * @property string|null $fokus_ringkas
 * @property \Illuminate\Support\Carbon|null $rencana_mulai
 * @property \Illuminate\Support\Carbon|null $rencana_selesai
 * @property string|null $cycle_id
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property string|null $catatan
 */
class ProgramTarget extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'annual_program_id', 'guru_id', 'fokus_ringkas',
        'rencana_mulai', 'rencana_selesai', 'cycle_id', 'generated_at', 'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rencana_mulai' => 'date',
            'rencana_selesai' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AnnualProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AnnualProgram::class, 'annual_program_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * @return BelongsTo<SupervisionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SupervisionCycle::class, 'cycle_id');
    }

    public function isGenerated(): bool
    {
        return $this->cycle_id !== null;
    }
}
