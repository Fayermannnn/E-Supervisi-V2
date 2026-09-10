<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Models\Report;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\Permission;

/**
 * Aturan lingkup akses laporan (M6) — dipakai bersama oleh Action ekspor dan
 * Policy unduh. Supervisor: laporan siklus binaannya. Admin Dinas: laporan
 * siklus & agregat dinasnya.
 */
final class ReportAccess
{
    public static function canView(User $user, Report $report): bool
    {
        if ($report->scope === Report::SCOPE_CYCLE) {
            $cycle = SupervisionCycle::query()->find($report->scope_id);

            return $cycle !== null && (
                $cycle->supervisor_id === $user->getKey()
                || $cycle->guru_id === $user->getKey()
                || ($user->isAdminDinas() && $user->adminDinasId() === $cycle->dinas_id)
            );
        }

        if ($report->scope === Report::SCOPE_DINAS) {
            return $user->can(Permission::ViewAggregateReport->value)
                && $user->adminDinasId() === $report->scope_id;
        }

        return false;
    }

    public static function canExport(User $user, Report $report): bool
    {
        if (! $user->can(Permission::ExportReport->value)) {
            return false;
        }

        // Guru tidak mengekspor meski dapat melihat laporan siklusnya.
        if ($report->scope === Report::SCOPE_CYCLE && $user->isGuru()) {
            return false;
        }

        return self::canView($user, $report);
    }
}
