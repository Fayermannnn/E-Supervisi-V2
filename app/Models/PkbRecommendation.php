<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain ProfessionalDev
 *
 * @provisional
 *
 * @property string $id
 * @property string $cycle_id
 * @property string $guru_id
 * @property string $pkb_catalog_item_id
 * @property string $sumber
 * @property string $alasan
 * @property string $status
 * @property string|null $direkomendasikan_oleh
 * @property \Illuminate\Support\Carbon|null $direspons_at
 */
class PkbRecommendation extends Model
{
    use HasUuids;

    public const SUMBER_ANALISIS = 'analisis';

    public const SUMBER_RTL_BERULANG = 'rtl_berulang';

    public const SUMBER_MANUAL = 'manual';

    public const STATUS_DISARANKAN = 'disarankan';

    public const STATUS_DIPILIH = 'dipilih';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_SELESAI = 'selesai';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id', 'guru_id', 'pkb_catalog_item_id', 'sumber', 'alasan',
        'status', 'direkomendasikan_oleh', 'direspons_at',
    ];

    protected $attributes = ['status' => self::STATUS_DISARANKAN, 'sumber' => self::SUMBER_ANALISIS];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['direspons_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<SupervisionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SupervisionCycle::class, 'cycle_id');
    }

    /**
     * @return BelongsTo<PkbCatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(PkbCatalogItem::class, 'pkb_catalog_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
