<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Reporting\Actions\BuildAggregateReport;
use App\Domain\Reporting\Actions\CompileCycleReport;
use App\Models\Report;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
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
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->fail(['authorization' => [$e->getMessage()]], 403);
        }

        return $this->ok($data);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
