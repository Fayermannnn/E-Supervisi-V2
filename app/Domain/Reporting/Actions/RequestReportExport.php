<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Reporting\Jobs\GenerateReportExport;
use App\Domain\Reporting\ReportAccess;
use App\Models\Report;
use App\Models\ReportExport;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Meminta ekspor berkas untuk sebuah laporan (M6). Membuat baris
 * `report_exports` (status antre) lalu men-dispatch job pembangkitan.
 */
class RequestReportExport
{
    private const FORMATS = [ReportExport::FORMAT_PDF, ReportExport::FORMAT_XLSX, ReportExport::FORMAT_CSV];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, Report $report, string $format): ReportExport
    {
        if (! in_array($format, self::FORMATS, true)) {
            throw new DomainException("Format ekspor tidak dikenal: {$format}.");
        }

        if (! ReportAccess::canExport($actor, $report)) {
            throw new AuthorizationException('Anda tidak berwenang mengekspor laporan ini.');
        }

        if ($report->snapshot()->doesntExist()) {
            throw new DomainException('Laporan belum memiliki data untuk diekspor.');
        }

        if ($report->scope === Report::SCOPE_CYCLE && $format !== ReportExport::FORMAT_PDF) {
            throw new DomainException('Laporan siklus hanya tersedia dalam format PDF.');
        }

        $export = ReportExport::create([
            'report_id' => $report->getKey(),
            'format' => $format,
            'status' => ReportExport::STATUS_ANTRE,
            'dibuat_oleh' => $actor->getKey(),
        ]);

        GenerateReportExport::dispatch($export->getKey());

        $this->audit->log('report.export_requested', $report, new: ['format' => $format], actor: $actor);

        return $export;
    }
}
