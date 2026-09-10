<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain ProfessionalDev
 *
 * @provisional Struktur dapat berubah additive setelah SLR Gate 6/7.
 *
 * @property string $id
 * @property string $judul
 * @property string $deskripsi
 * @property string|null $penyelenggara
 * @property string $tipe
 * @property string|null $tautan
 * @property list<string>|null $tags
 * @property list<string>|null $kompetensi
 * @property int|null $durasi_jam
 * @property string|null $pemilik_dinas_id
 * @property string $status
 * @property string $created_by
 */
class PkbCatalogItem extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_TERBIT = 'terbit';

    public const STATUS_ARSIP = 'arsip';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judul', 'deskripsi', 'penyelenggara', 'tipe', 'tautan',
        'tags', 'kompetensi', 'durasi_jam', 'pemilik_dinas_id', 'status', 'created_by',
    ];

    protected $attributes = ['status' => self::STATUS_DRAFT, 'tipe' => 'mandiri'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'kompetensi' => 'array',
            'durasi_jam' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Dinas, $this>
     */
    public function pemilikDinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class, 'pemilik_dinas_id');
    }

    /**
     * Item terbit yang berlaku untuk sebuah dinas: global (pemilik null) atau milik dinas itu.
     *
     * @param  Builder<PkbCatalogItem>  $query
     */
    public function scopeAvailableFor(Builder $query, ?string $dinasId): void
    {
        $query->where('status', self::STATUS_TERBIT)
            ->where(function (Builder $q) use ($dinasId): void {
                $q->whereNull('pemilik_dinas_id');
                if ($dinasId !== null) {
                    $q->orWhere('pemilik_dinas_id', $dinasId);
                }
            });
    }
}
