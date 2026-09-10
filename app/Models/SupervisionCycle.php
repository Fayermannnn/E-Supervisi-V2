<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Enums\CycleStatus;
use Database\Factories\SupervisionCycleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Supervision
 *
 * @property string $id
 * @property string $guru_id
 * @property string $supervisor_id
 * @property string $sekolah_id
 * @property string $dinas_id
 * @property string|null $program_id
 * @property string $tahun_ajaran
 * @property string $semester
 * @property string $judul
 * @property string|null $fokus_ringkas
 * @property CycleStatus $status
 * @property string|null $canceled_reason
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class SupervisionCycle extends Model
{
    /** @use HasFactory<SupervisionCycleFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'guru_id',
        'supervisor_id',
        'sekolah_id',
        'dinas_id',
        'program_id',
        'tahun_ajaran',
        'semester',
        'judul',
        'fokus_ringkas',
        'status',
        'canceled_reason',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CycleStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => CycleStatus::Draft->value,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    /**
     * @return BelongsTo<AnnualProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AnnualProgram::class, 'program_id');
    }

    /**
     * @return HasMany<CycleStatusTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(CycleStatusTransition::class, 'cycle_id')->orderBy('created_at');
    }

    /**
     * @return HasOne<PlanningAgreement, $this>
     */
    public function planningAgreement(): HasOne
    {
        return $this->hasOne(PlanningAgreement::class, 'cycle_id');
    }

    /**
     * @return HasMany<TeacherReflection, $this>
     */
    public function reflections(): HasMany
    {
        return $this->hasMany(TeacherReflection::class, 'cycle_id');
    }

    /**
     * @return HasMany<Observation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'cycle_id');
    }

    /**
     * @param  Builder<SupervisionCycle>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isAdminSistem()) {
            $query->whereRaw('1 = 0'); // admin sistem tidak melihat konten pedagogis

            return;
        }

        if ($user->isAdminDinas()) {
            $query->where('dinas_id', $user->adminDinasId());

            return;
        }

        if ($user->isSupervisor()) {
            $query->where('supervisor_id', $user->getKey());

            return;
        }

        if ($user->isGuru()) {
            $query->where('guru_id', $user->getKey());

            return;
        }

        $query->whereRaw('1 = 0');
    }

    public function isActive(): bool
    {
        return ! $this->status->isTerminal();
    }
}
