<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\Report;
use App\Models\ReportSnapshot;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Memateri­alisasi laporan agregat dinas (M6) menjadi baris `reports` +
 * `report_snapshots` agar dapat diekspor. Data dari `BuildAggregateReport`
 * (otorisasi ditegakkan di sana).
 */
class CompileAggregateReport
{
    public function __construct(
        private readonly BuildAggregateReport $builder,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{tahun_ajaran?: ?string, wilayah?: ?string, jenjang?: ?string}  $filter
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $adminDinas, array $filter = []): Report
    {
        $data = $this->builder->handle($adminDinas, $filter);

        $dinasId = $adminDinas->adminDinasId();
        if ($dinasId === null) {
            throw new DomainException('Akun Anda tidak tertaut ke sebuah dinas.');
        }

        return DB::transaction(function () use ($adminDinas, $dinasId, $filter, $data): Report {
            $report = Report::updateOrCreate(
                ['scope' => Report::SCOPE_DINAS, 'scope_id' => $dinasId],
                [
                    'tipe' => 'agregat_dinas',
                    'filter' => $filter,
                    'dibuat_oleh' => $adminDinas->getKey(),
                    'format' => 'xlsx',
                    'status' => 'siap',
                ],
            );

            ReportSnapshot::where('report_id', $report->id)->delete();
            ReportSnapshot::create([
                'report_id' => $report->id,
                'data' => $data,
                'generated_at' => now(),
            ]);

            $this->audit->log('report.aggregate_compiled', $report, new: ['filter' => $filter], actor: $adminDinas);

            return $report->refresh();
        });
    }
}
