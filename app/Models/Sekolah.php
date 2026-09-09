<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SekolahFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Organization
 *
 * @property string $id
 * @property string $dinas_id
 * @property string $nama
 * @property string|null $npsn
 * @property string $jenjang
 * @property string|null $kecamatan
 * @property string|null $wilayah
 */
class Sekolah extends Model
{
    /** @use HasFactory<SekolahFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'sekolah';

    /**
     * @var list<string>
     */
    protected $fillable = ['dinas_id', 'nama', 'npsn', 'jenjang', 'kecamatan', 'wilayah', 'alamat'];

    /**
     * @return BelongsTo<Dinas, $this>
     */
    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
