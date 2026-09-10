<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reporting\Actions\BuildAggregateReport;
use App\Domain\Reporting\Actions\CompileAggregateReport;
use App\Domain\Reporting\Actions\CompileCycleReport;
use App\Domain\Reporting\Actions\RequestReportExport;
use App\Models\Report;
use App\Models\ReportExport;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ReportController extends ApiController
{
    /**
     * GET /reports/cycle/{cycle} — laporan per siklus.
     */
    public function cycle(Request $request, SupervisionCycle $cycle, CompileCycleReport $compile): JsonResponse
    {
        $this->authorize('view', $cycle);

        $report = Report::with('snapshot')->where('scope', 'cycle')->where('scope_id', $cycle->id)->first();

        if ($report === null && $request->boolean('compile')) {
            try {
                $report = $compile->handle($this->user($request), $cycle, $request->string('override_note')->toString() ?: null);
                $report->load('snapshot');
            } catch (DomainException|RuntimeException $e) {
                return $this->fail(['report' => [$e->getMessage()]]);
            }
        }

        if ($report === null) {
            return $this->fail(['report' => ['Laporan belum disusun.']], 404);
        }

        return $this->ok([
            'status' => $report->status,
            'snapshot' => $report->snapshot?->data,
            'generated_at' => $report->snapshot?->generated_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /reports/aggregate — laporan agregat lintas sekolah (Admin Dinas).
     */
    public function aggregate(Request $request, BuildAggregateReport $builder): JsonResponse
    {
        try {
            $data = $builder->handle($this->user($request), $request->only(['tahun_ajaran', 'wilayah', 'jenjang']));
        } catch (AuthorizationException $e) {
            return $this->fail(['authorization' => [$e->getMessage()]], 403);
        }

        return $this->ok($data);
    }

    /**
     * POST /cycles/{cycle}/reports/export — minta ekspor laporan siklus (PDF).
     */
    public function exportCycle(Request $request, SupervisionCycle $cycle, RequestReportExport $action): JsonResponse
    {
        $report = Report::where('scope', 'cycle')->where('scope_id', $cycle->id)->first();
        if ($report === null) {
            return $this->fail(['report' => ['Susun laporan siklus terlebih dahulu.']], 404);
        }

        $format = $request->string('format')->toString() ?: ReportExport::FORMAT_PDF;

        return $this->dispatchExport($action, $this->user($request), $report, $format);
    }

    /**
     * POST /reports/aggregate/export — minta ekspor laporan agregat (PDF/XLSX/CSV).
     */
    public function exportAggregate(Request $request, CompileAggregateReport $compile, RequestReportExport $action): JsonResponse
    {
        $data = $request->validate([
            'format' => ['required', 'in:pdf,xlsx,csv'],
            'tahun_ajaran' => ['nullable', 'string'],
            'wilayah' => ['nullable', 'string'],
            'jenjang' => ['nullable', 'string'],
        ]);

        try {
            $report = $compile->handle($this->user($request), array_filter([
                'tahun_ajaran' => $data['tahun_ajaran'] ?? null,
                'wilayah' => $data['wilayah'] ?? null,
                'jenjang' => $data['jenjang'] ?? null,
            ]));
        } catch (AuthorizationException $e) {
            return $this->fail(['authorization' => [$e->getMessage()]], 403);
        } catch (DomainException $e) {
            return $this->fail(['report' => [$e->getMessage()]]);
        }

        return $this->dispatchExport($action, $this->user($request), $report, $data['format']);
    }

    /**
     * GET /reports/exports/{export} — status berkas ekspor.
     */
    public function exportStatus(Request $request, ReportExport $export): JsonResponse
    {
        $this->authorizeForUser($this->user($request), 'download', $export);

        return $this->ok([
            'status' => $export->status,
            'format' => $export->format,
            'ukuran' => $export->ukuran,
            'error' => $export->error,
            'download_url' => $export->isReady() ? route('reports.exports.download', $export) : null,
        ]);
    }

    private function dispatchExport(RequestReportExport $action, User $actor, Report $report, string $format): JsonResponse
    {
        try {
            $export = $action->handle($actor, $report, $format);
        } catch (AuthorizationException $e) {
            return $this->fail(['authorization' => [$e->getMessage()]], 403);
        } catch (DomainException $e) {
            return $this->fail(['report' => [$e->getMessage()]]);
        }

        return $this->ok([
            'id' => $export->id,
            'status' => $export->status,
            'download_url' => $export->refresh()->isReady() ? route('reports.exports.download', $export) : null,
        ], status: 202);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
