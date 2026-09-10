<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Jobs;

use App\Domain\Reporting\Rendering\AggregateReportTable;
use App\Domain\Reporting\Rendering\PdfRenderer;
use App\Domain\Reporting\Rendering\TabularWriter;
use App\Models\Report;
use App\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Membangkitkan berkas ekspor laporan dan menyimpannya ke disk privat (M6).
 * Idempoten: hanya memproses baris berstatus antre/gagal.
 */
class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly string $exportId) {}

    public function handle(PdfRenderer $pdf, TabularWriter $tabular): void
    {
        $export = ReportExport::query()->find($this->exportId);
        if ($export === null || ! in_array($export->status, [ReportExport::STATUS_ANTRE, ReportExport::STATUS_GAGAL], true)) {
            return;
        }

        $export->forceFill(['status' => ReportExport::STATUS_DIPROSES, 'error' => null])->save();

        try {
            $report = $export->report()->sole();
            $data = $report->snapshot()->sole()->data;

            $content = $this->build($report, $export->format, $data, $pdf, $tabular);

            $path = "reports/{$report->getKey()}/{$export->getKey()}.{$export->format}";
            Storage::disk('local')->put($path, $content);

            $export->forceFill([
                'disk' => 'local',
                'path' => $path,
                'ukuran' => strlen($content),
                'status' => ReportExport::STATUS_SIAP,
                'selesai_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $export->forceFill([
                'status' => ReportExport::STATUS_GAGAL,
                'error' => Str::limit($e->getMessage(), 2000, ''),
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function build(Report $report, string $format, array $data, PdfRenderer $pdf, TabularWriter $tabular): string
    {
        if ($report->scope === Report::SCOPE_CYCLE) {
            return $pdf->render('reports.cycle-pdf', ['s' => $data]);
        }

        $table = AggregateReportTable::fromSnapshot($data);
        $meta = [
            'digenerate_pada' => (string) ($data['digenerate_pada'] ?? now()->toIso8601String()),
            'filter' => is_array($data['filter'] ?? null) ? $data['filter'] : [],
        ];

        return match ($format) {
            ReportExport::FORMAT_CSV => $tabular->csv($table['per_sekolah']['headers'], $table['per_sekolah']['rows']),
            ReportExport::FORMAT_XLSX => $tabular->xlsx([
                'Ringkasan' => [
                    'headers' => ['Indikator', 'Nilai'],
                    'rows' => array_map(static fn (array $r): array => [$r['label'], $r['nilai']], $table['ringkasan']),
                ],
                'Per sekolah' => $table['per_sekolah'],
            ]),
            default => $pdf->render('reports.aggregate-pdf', ['table' => $table, 'meta' => $meta], 'landscape'),
        };
    }
}
