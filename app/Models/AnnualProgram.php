<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain Program
 *
 * @property string $id
 * @property string $owner_id
 * @property string $dinas_id
 * @property string $tahun_ajaran
 * @property string $semester
 * @property string $judul
 * @property string|null $catatan
 * @property string $status
 */
class AnnualProgram extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    /**
     * @var list<string>
     */
    protected $fillable = ['owner_id', 'dinas_id', 'tahun_ajaran', 'semester', 'judul', 'catatan', 'status'];

    protected $attributes = ['status' => self::STATUS_DRAFT];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<ProgramTarget, $this>
     */
    public function targets(): HasMany
    {
        return $this->hasMany(ProgramTarget::class)->orderBy('created_at');
    }

    /**
     * @param  Builder<AnnualProgram>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('owner_id', $user->getKey());
    }

    public function isMutable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_AKTIF], true);
    }
}
