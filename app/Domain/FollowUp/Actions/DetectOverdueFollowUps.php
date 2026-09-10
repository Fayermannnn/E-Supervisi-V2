<?php

declare(strict_types=1);

namespace App\Domain\FollowUp\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\FollowUp\Notifications\FollowUpEscalationNotification;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\FollowUpPlan;
use App\Models\SupervisionCycle;
use App\Support\Enums\CycleStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Job harian (M5, Spec §5 status 6). Menandai RTL yang lewat tenggat sebagai
 * `terlambat`, memicu transisi siklus ke FOLLOW_UP_OVERDUE, dan mengeskalasi
 * ke supervisor. Kebalikannya: bila semua RTL kembali beres, siklus kembali
 * ke FOLLOW_UP_ACTIVE.
 */
class DetectOverdueFollowUps
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CycleStateMachine $stateMachine,
    ) {}

    /**
     * @return array{marked_overdue: int, recovered: int}
     */
    public function handle(): array
    {
        $today = now()->startOfDay();
        $markedOverdue = 0;
        $recovered = 0;

        FollowUpPlan::query()
            ->where('status', FollowUpPlan::STATUS_BERJALAN)
            ->whereDate('tenggat', '<', $today)
            ->chunkById(200, function ($plans) use (&$markedOverdue): void {
                foreach ($plans as $plan) {
                    $plan->update(['status' => FollowUpPlan::STATUS_TERLAMBAT]);
                    $markedOverdue++;
                }
            });

        // Perbarui status siklus + eskalasi.
        SupervisionCycle::query()
            ->whereIn('status', [CycleStatus::FollowUpActive->value, CycleStatus::FollowUpOverdue->value])
            ->with(['supervisor'])
            ->chunkById(200, function ($cycles) use (&$recovered): void {
                foreach ($cycles as $cycle) {
                    $hasOverdue = FollowUpPlan::query()
                        ->where('cycle_id', $cycle->id)
                        ->where('status', FollowUpPlan::STATUS_TERLAMBAT)
                        ->exists();

                    if ($hasOverdue && $cycle->status === CycleStatus::FollowUpActive) {
                        $this->stateMachine->transition($cycle, CycleStatus::FollowUpOverdue, null);
                        $this->escalate($cycle);
                    } elseif (! $hasOverdue && $cycle->status === CycleStatus::FollowUpOverdue) {
                        $this->stateMachine->transition($cycle, CycleStatus::FollowUpActive, null);
                        $recovered++;
                    }
                }
            });

        return ['marked_overdue' => $markedOverdue, 'recovered' => $recovered];
    }

    private function escalate(SupervisionCycle $cycle): void
    {
        $supervisor = $cycle->supervisor()->first();
        if ($supervisor === null) {
            return;
        }

        DB::transaction(function () use ($cycle, $supervisor): void {
            Notification::send($supervisor, new FollowUpEscalationNotification($cycle));
            $this->audit->log('followup.escalated', $cycle, context: ['to' => $supervisor->getKey()]);
        });
    }
}
