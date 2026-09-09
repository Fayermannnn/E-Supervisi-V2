<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DinasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @domain Organization
 *
 * @property string $id
 * @property string $nama
 * @property string $kode
 * @property string $tipe
 * @property string $provinsi
 */
class Dinas extends Model
{
    /** @use HasFactory<DinasFactory> */
    use HasFactory, HasUuids;

    protected $table = 'dinas';

    /**
     * @var list<string>
     */
    protected $fillable = ['nama', 'kode', 'tipe', 'provinsi'];

    /**
     * @return HasMany<Sekolah, $this>
     */
    public function sekolah(): HasMany
    {
        return $this->hasMany(Sekolah::class);
    }
}
