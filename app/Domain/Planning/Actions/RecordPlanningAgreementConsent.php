<?php

declare(strict_types=1);

namespace App\Domain\Planning\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Mencatat persetujuan salah satu pihak atas kesepakatan pra-observasi.
 * Bila kedua pihak sudah menyetujui, siklus otomatis bertransisi ke Terjadwal.
 */
class RecordPlanningAgreementConsent
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CycleStateMachine $stateMachine,
    ) {}

    /**
     * @throws DomainException
     */
    public function handle(User $actor, SupervisionCycle $cycle): SupervisionCycle
    {
        $agreement = $cycle->planningAgreement()->first();

        if ($agreement === null) {
            throw new DomainException('Kesepakatan pra-observasi belum diisi.');
        }

        if ($cycle->status !== CycleStatus::Draft) {
            throw new DomainException('Persetujuan hanya berlaku saat siklus berstatus Draf.');
        }

        $isGuru = $cycle->guru_id === $actor->getKey();
        $isSupervisor = $cycle->supervisor_id === $actor->getKey();

        if (! $isGuru && ! $isSupervisor) {
            throw new DomainException('Anda bukan pihak dalam siklus ini.');
        }

        DB::transaction(function () use ($actor, $cycle, $agreement, $isGuru): void {
            $agreement->forceFill([
                $isGuru ? 'disepakati_guru_at' : 'disepakati_supervisor_at' => now(),
            ])->save();

            $this->audit->log(
                $isGuru ? 'planning_agreement.consent_guru' : 'planning_agreement.consent_supervisor',
                $cycle,
                actor: $actor,
            );
        });

        $cycle->refresh();

        if ($cycle->planningAgreement()->first()?->isFullyAgreed()) {
            $this->stateMachine->transition($cycle, CycleStatus::Scheduled, $cycle->supervisor()->sole());
        }

        return $cycle->refresh();
    }
}
