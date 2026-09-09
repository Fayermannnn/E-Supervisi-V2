<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Instruments\Enums\InstrumentStatus;
use Database\Factories\InstrumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Instruments
 *
 * @property string $id
 * @property string $code
 * @property string $nama
 * @property string|null $deskripsi
 * @property string|null $pemilik_dinas_id
 * @property InstrumentStatus $status
 */
class Instrument extends Model
{
    /** @use HasFactory<InstrumentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = ['code', 'nama', 'deskripsi', 'pemilik_dinas_id', 'status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => InstrumentStatus::class];
    }

    /**
     * @return HasMany<InstrumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(InstrumentVersion::class);
    }

    /**
     * @return BelongsTo<Dinas, $this>
     */
    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class, 'pemilik_dinas_id');
    }

    public function latestPublishedVersion(): ?InstrumentVersion
    {
        return $this->versions()
            ->whereNotNull('published_at')
            ->orderByDesc('version')
            ->first();
    }

    public function isGlobal(): bool
    {
        return $this->pemilik_dinas_id === null;
    }
}
