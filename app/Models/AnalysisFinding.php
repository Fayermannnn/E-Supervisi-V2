<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Analysis
 *
 * @property string $analysis_result_id
 * @property string $kategori
 * @property string $deskripsi
 * @property array<string, mixed>|null $bukti_ref
 * @property int $prioritas
 * @property int $urutan
 */
class AnalysisFinding extends Model
{
    use HasUuids;

    public const KATEGORI_KEKUATAN = 'kekuatan';

    public const KATEGORI_PENGEMBANGAN = 'area_pengembangan';

    public const KATEGORI_POLA = 'pola';

    /**
     * @var list<string>
     */
    protected $fillable = ['analysis_result_id', 'kategori', 'deskripsi', 'bukti_ref', 'prioritas', 'urutan'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bukti_ref' => 'array',
            'prioritas' => 'integer',
            'urutan' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AnalysisResult, $this>
     */
    public function analysisResult(): BelongsTo
    {
        return $this->belongsTo(AnalysisResult::class);
    }
}
