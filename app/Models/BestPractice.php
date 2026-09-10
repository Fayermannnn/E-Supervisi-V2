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
 * @provisional
 *
 * Kurasi praktik baik antar-guru. Alur: nominasi supervisor → consent guru
 * (UU PDP) → kurasi admin dinas → terbit dinas-wide.
 *
 * @property string $id
 * @property string $cycle_id
 * @property string $guru_id
 * @property string $dinas_id
 * @property string|null $sekolah_id
 * @property string $nominated_by
 * @property string $judul
 * @property string $ringkasan
 * @property string $praktik
 * @property list<string>|null $tags
 * @property string|null $skor_band
 * @property bool $anonim
 * @property string|null $consent_by
 * @property \Illuminate\Support\Carbon|null $consent_at
 * @property string|null $curated_by
 * @property \Illuminate\Support\Carbon|null $curated_at
 * @property string|null $catatan_kurasi
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $terbit_at
 */
class BestPractice extends Model
{
    use HasUuids;

    public const STATUS_MENUNGGU_CONSENT = 'menunggu_consent';

    public const STATUS_MENUNGGU_KURASI = 'menunggu_kurasi';

    public const STATUS_TERBIT = 'terbit';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DITARIK = 'ditarik';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id', 'guru_id', 'dinas_id', 'sekolah_id', 'nominated_by',
        'judul', 'ringkasan', 'praktik', 'tags', 'skor_band', 'anonim',
        'consent_by', 'consent_at', 'curated_by', 'curated_at', 'catatan_kurasi',
        'status', 'terbit_at',
    ];

    protected $attributes = ['status' => self::STATUS_MENUNGGU_CONSENT, 'anonim' => false];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'anonim' => 'boolean',
            'consent_at' => 'datetime',
            'curated_at' => 'datetime',
            'terbit_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupervisionCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SupervisionCycle::class, 'cycle_id');
    }

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
    public function nominator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    /**
     * @param  Builder<BestPractice>  $query
     */
    public function scopePublishedInDinas(Builder $query, string $dinasId): void
    {
        $query->where('status', self::STATUS_TERBIT)->where('dinas_id', $dinasId);
    }

    public function displayGuruName(): string
    {
        if ($this->anonim) {
            return 'Guru (dianonimkan)';
        }

        return $this->guru()->sole()->name;
    }
}
