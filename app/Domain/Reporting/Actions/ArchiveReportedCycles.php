<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Actions;

use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\SupervisionCycle;
use App\Support\Enums\CycleStatus;

/**
 * Job retensi (Spec §5 status 8, §10). Mengarsipkan siklus DILAPORKAN yang
 * sudah melewati ambang usia. Data TIDAK dihapus — hanya dikunci historis.
 */
class ArchiveReportedCycles
{
    public function __construct(private readonly CycleStateMachine $stateMachine) {}

    public function handle(int $olderThanDays = 90): int
    {
        $cutoff = now()->subDays($olderThanDays);
        $archived = 0;

        SupervisionCycle::query()
            ->where('status', CycleStatus::Reported->value)
            ->where('updated_at', '<', $cutoff)
            ->chunkById(200, function ($cycles) use (&$archived): void {
                foreach ($cycles as $cycle) {
                    $this->stateMachine->transition($cycle, CycleStatus::Archived, null);
                    $archived++;
                }
            });

        return $archived;
    }
}
