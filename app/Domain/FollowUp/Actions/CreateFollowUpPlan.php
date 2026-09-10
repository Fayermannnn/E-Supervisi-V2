<?php

declare(strict_types=1);

namespace App\Domain\FollowUp\Actions;

use App\Domain\Administration\PolicySettings;
use App\Domain\Audit\AuditLogger;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\FollowUpPlan;
use App\Models\ReminderSchedule;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Membuat RTL (M5). Menjadwalkan pengingat H-n dan memicu transisi
 * FEEDBACK_GIVEN → FOLLOW_UP_ACTIVE.
 */
class CreateFollowUpPlan
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CycleStateMachine $stateMachine,
        private readonly PolicySettings $policies,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $items  tiap entri: deskripsi, indikator_keberhasilan, tenggat_item?
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, SupervisionCycle $cycle, string $tujuan, string $tenggat, array $items): FollowUpPlan
    {
        if (! $actor->can(Permission::CreateFollowUp->value) || $cycle->supervisor_id !== $actor->getKey()) {
            throw new AuthorizationException('Anda tidak berwenang membuat RTL untuk siklus ini.');
        }

        if (! in_array($cycle->status, [CycleStatus::FeedbackGiven, CycleStatus::FollowUpActive, CycleStatus::FollowUpOverdue], true)) {
            throw new DomainException('RTL dibuat setelah umpan balik dikonfirmasi guru.');
        }

        if ($items === []) {
            throw new DomainException('RTL harus memiliki minimal satu butir kegiatan.');
        }

        return DB::transaction(function () use ($actor, $cycle, $tujuan, $tenggat, $items): FollowUpPlan {
            $plan = FollowUpPlan::create([
                'cycle_id' => $cycle->getKey(),
                'tujuan' => $tujuan,
                'dibuat_oleh' => $actor->getKey(),
                'mulai' => now()->toDateString(),
                'tenggat' => $tenggat,
                'status' => FollowUpPlan::STATUS_BERJALAN,
            ]);

            foreach ($items as $i => $item) {
                $plan->items()->create([
                    'deskripsi' => $item['deskripsi'],
                    'indikator_keberhasilan' => $item['indikator_keberhasilan'],
                    'tenggat_item' => $item['tenggat_item'] ?? null,
                    'status' => \App\Models\FollowUpItem::STATUS_BELUM,
                    'urutan' => $i,
                ]);
            }

            $this->scheduleReminders($plan, $cycle);

            $this->audit->log('followup.created', $cycle, new: ['tenggat' => $tenggat, 'butir' => count($items)], actor: $actor);

            if ($cycle->status === CycleStatus::FeedbackGiven) {
                $this->stateMachine->transition($cycle, CycleStatus::FollowUpActive, $actor);
            }

            return $plan->refresh();
        });
    }

    private function scheduleReminders(FollowUpPlan $plan, SupervisionCycle $cycle): void
    {
        /** @var list<int> $daysBefore */
        $daysBefore = (array) $this->policies->get('followup.reminder_days_before', $cycle->dinas_id);
        $tenggat = Carbon::parse($plan->tenggat);
        $guruId = $cycle->guru_id;

        foreach ($daysBefore as $days) {
            $sendAt = $tenggat->copy()->subDays((int) $days)->setTime(7, 0);
            if ($sendAt->isPast()) {
                continue;
            }

            ReminderSchedule::create([
                'remindable_type' => $plan->getMorphClass(),
                'remindable_id' => $plan->getKey(),
                'user_id' => $guruId,
                'kind' => 'followup.due_soon',
                'channel' => 'app',
                'send_at' => $sendAt,
                'payload' => [
                    'title' => 'Tenggat RTL mendekat',
                    'body' => "Rencana tindak lanjut \"{$cycle->judul}\" jatuh tempo ".$tenggat->translatedFormat('d M Y').'.',
                    'url' => route('cycles.show', $cycle),
                ],
            ]);
        }
    }
}
