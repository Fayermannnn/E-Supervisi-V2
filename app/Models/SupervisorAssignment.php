<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SupervisorAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Penugasan supervisor → guru binaan (ADR-012).
 *
 * @domain Organization
 *
 * @property string $supervisor_id
 * @property string $guru_id
 * @property Carbon|null $mulai
 * @property Carbon|null $selesai
 */
class SupervisorAssignment extends Model
{
    /** @use HasFactory<SupervisorAssignmentFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = ['supervisor_id', 'guru_id', 'created_by', 'mulai', 'selesai'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * @param  Builder<SupervisorAssignment>  $query
     */
    public function scopeActiveOn(Builder $query, ?Carbon $date = null): void
    {
        $date ??= Carbon::now();

        $query->whereDate('mulai', '<=', $date)
            ->where(function (Builder $q) use ($date): void {
                $q->whereNull('selesai')->orWhereDate('selesai', '>=', $date);
            });
    }
}
