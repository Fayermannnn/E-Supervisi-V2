<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Actions;

use App\Models\FollowUpPlan;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Permission;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Laporan agregat lintas sekolah untuk Admin Dinas (M6, Spec §8
 * GET /reports/aggregate). Tidak membocorkan data individual guru di luar
 * kebijakan akses.
 *
 * @phpstan-type Filter array{tahun_ajaran?: ?string, wilayah?: ?string, jenjang?: ?string}
 */
class BuildAggregateReport
{
    /**
     * @param  Filter  $filter
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function handle(User $adminDinas, array $filter = []): array
    {
        if (! $adminDinas->can(Permission::ViewAggregateReport->value)) {
            throw new AuthorizationException('Anda tidak berwenang melihat laporan agregat.');
        }

        $dinasId = $adminDinas->adminDinasId();

        $cycles = SupervisionCycle::query()
            ->where('dinas_id', $dinasId)
            ->when($filter['tahun_ajaran'] ?? null, fn ($q, $ta) => $q->where('tahun_ajaran', $ta))
            ->when($filter['wilayah'] ?? null, fn ($q, $w) => $q->whereHas('sekolah', fn ($q) => $q->where('wilayah', $w)))
            ->when($filter['jenjang'] ?? null, fn ($q, $j) => $q->whereHas('sekolah', fn ($q) => $q->where('jenjang', $j)))
            ->with('sekolah:id,nama,wilayah,jenjang')
            ->get();

        $byStatus = $cycles->groupBy(fn (SupervisionCycle $c) => $c->status->label())->map->count();

        $bySekolah = $cycles->groupBy('sekolah_id')->map(function ($group) {
            $first = $group->first();

            return [
                'sekolah' => $first?->sekolah?->nama,
                'wilayah' => $first?->sekolah?->wilayah,
                'total' => $group->count(),
                'dilaporkan' => $group->where('status', CycleStatus::Reported)->count(),
                'tindak_lanjut_terlambat' => $group->where('status', CycleStatus::FollowUpOverdue)->count(),
            ];
        })->values();

        $rtl = FollowUpPlan::query()
            ->whereHas('cycle', fn ($q) => $q->where('dinas_id', $dinasId))
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        // Anomali sederhana untuk penandaan (bukan aksi otomatis).
        $flags = [];
        foreach ($bySekolah as $row) {
            if ($row['total'] >= 3 && $row['dilaporkan'] === 0) {
                $flags[] = "{$row['sekolah']}: {$row['total']} siklus, belum ada yang dilaporkan.";
            }
            if ($row['tindak_lanjut_terlambat'] > 0) {
                $flags[] = "{$row['sekolah']}: {$row['tindak_lanjut_terlambat']} siklus dengan RTL terlambat.";
            }
        }

        return [
            'filter' => $filter,
            'total_siklus' => $cycles->count(),
            'per_status' => $byStatus,
            'per_sekolah' => $bySekolah,
            'rtl_per_status' => $rtl,
            'anomali' => $flags,
            'digenerate_pada' => now()->toIso8601String(),
        ];
    }
}
