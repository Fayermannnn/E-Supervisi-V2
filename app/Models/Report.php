<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @domain Reporting
 *
 * @property string $scope
 * @property string|null $scope_id
 * @property string $tipe
 * @property string $format
 * @property string $status
 * @property array<string, mixed>|null $filter
 * @property \Illuminate\Support\Carbon|null $periode_mulai
 * @property \Illuminate\Support\Carbon|null $periode_selesai
 */
class Report extends Model
{
    use HasUuids;

    public const SCOPE_CYCLE = 'cycle';

    public const SCOPE_DINAS = 'dinas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'scope',
        'scope_id',
        'tipe',
        'periode_mulai',
        'periode_selesai',
        'filter',
        'dibuat_oleh',
        'format',
        'file_disk',
        'file_path',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filter' => 'array',
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
        ];
    }

    /**
     * @return HasOne<ReportSnapshot, $this>
     */
    public function snapshot(): HasOne
    {
        return $this->hasOne(ReportSnapshot::class);
    }
}
