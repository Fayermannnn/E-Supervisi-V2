<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Reporting
 *
 * @property string $id
 * @property string $report_id
 * @property string $format
 * @property string|null $disk
 * @property string|null $path
 * @property int|null $ukuran
 * @property string $status
 * @property string|null $error
 * @property string|null $dibuat_oleh
 * @property \Illuminate\Support\Carbon|null $selesai_at
 */
class ReportExport extends Model
{
    use HasUuids;

    public const FORMAT_PDF = 'pdf';

    public const FORMAT_XLSX = 'xlsx';

    public const FORMAT_CSV = 'csv';

    public const STATUS_ANTRE = 'antre';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SIAP = 'siap';

    public const STATUS_GAGAL = 'gagal';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'report_id', 'format', 'disk', 'path', 'ukuran', 'status', 'error', 'dibuat_oleh', 'selesai_at',
    ];

    protected $attributes = ['status' => self::STATUS_ANTRE];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['ukuran' => 'integer', 'selesai_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Report, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_SIAP && $this->path !== null;
    }

    public function downloadName(): string
    {
        $slug = str($this->report()->sole()->tipe)->slug();

        return "laporan-{$slug}-".($this->created_at ?? now())->format('Ymd').'.'.$this->format;
    }
}
