<?php

declare(strict_types=1);

namespace App\Domain\Supervision\StateMachine;

use App\Domain\Audit\AuditLogger;
use App\Domain\Supervision\Events\CycleTransitioned;
use App\Domain\Supervision\Exceptions\InvalidTransitionException;
use App\Models\CycleStatusTransition;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus as S;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya pintu perubahan status siklus (docs/state-machine.md, ADR-004).
 *
 * Setiap transisi: valid → guard lulus → terotorisasi → dalam transaksi
 * (update status + baris transitions + audit + event).
 */
class CycleStateMachine
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Daftar transisi legal: from => [to => [roles]].
     * "system" = dipicu job otomatis (actor null).
     *
     * @return array<int, array<int, list<string>>>
     */
    public static function map(): array
    {
        return [
            S::Draft->value => [
                S::Scheduled->value => ['supervisor'],
                S::Canceled->value => ['supervisor'],
            ],
            S::Scheduled->value => [
                S::ObservationDone->value => ['supervisor'],
                S::Canceled->value => ['supervisor'],
            ],
            S::ObservationDone->value => [
                S::AnalysisDone->value => ['supervisor'],
                S::Canceled->value => ['supervisor'],
            ],
            S::AnalysisDone->value => [
                S::FeedbackGiven->value => ['supervisor'],
                S::Canceled->value => ['supervisor'],
            ],
            S::FeedbackGiven->value => [
                S::FollowUpActive->value => ['supervisor'],
                S::Canceled->value => ['supervisor'],
            ],
            S::FollowUpActive->value => [
                S::FollowUpOverdue->value => ['system'],
                S::Reported->value => ['supervisor'],
            ],
            S::FollowUpOverdue->value => [
                S::FollowUpActive->value => ['system'],
                S::Reported->value => ['supervisor'],
            ],
            S::Reported->value => [
                S::Archived->value => ['system'],
            ],
        ];
    }

    public function can(SupervisionCycle $cycle, S $to): bool
    {
        return isset(self::map()[$cycle->status->value][$to->value]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     *
     * @throws InvalidTransitionException
     */
    public function transition(
        SupervisionCycle $cycle,
        S $to,
        ?User $actor,
        ?string $reason = null,
        array $metadata = [],
    ): SupervisionCycle {
        $from = $cycle->status;

        if ($from === $to) {
            $this->audit->log('cycle.transition_noop', $cycle, context: ['status' => $to->value], actor: $actor);

            return $cycle;
        }

        $allowedRoles = self::map()[$from->value][$to->value] ?? null;

        if ($allowedRoles === null) {
            throw InvalidTransitionException::notAllowed($from, $to);
        }

        $this->assertActorAllowed($allowedRoles, $actor, $from, $to);
        $this->guard($cycle, $to, $reason);

        return DB::transaction(function () use ($cycle, $from, $to, $actor, $reason, $metadata): SupervisionCycle {
            $cycle->status = $to;

            if ($to === S::Canceled) {
                $cycle->canceled_reason = $reason;
            }
            if ($to === S::Archived) {
                $cycle->archived_at = now();
            }

            $cycle->save();

            $transition = CycleStatusTransition::create([
                'cycle_id' => $cycle->getKey(),
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_id' => $actor?->getKey(),
                'actor_role' => $actor === null ? 'system' : $actor->roles()->map(fn ($r) => $r->value)->join(','),
                'reason' => $reason,
                'metadata' => $metadata === [] ? null : $metadata,
            ]);

            $this->audit->log('cycle.transitioned', $cycle, old: ['status' => $from->value], new: ['status' => $to->value], context: ['reason' => $reason], actor: $actor);

            CycleTransitioned::dispatch($cycle, $from, $to, $transition);

            return $cycle;
        });
    }

    /**
     * @param  list<string>  $allowedRoles
     */
    private function assertActorAllowed(array $allowedRoles, ?User $actor, S $from, S $to): void
    {
        if (in_array('system', $allowedRoles, true) && $actor === null) {
            return;
        }

        if ($actor === null) {
            throw InvalidTransitionException::guardFailed($from, $to, 'transisi ini butuh aktor manusia.');
        }

        foreach ($allowedRoles as $role) {
            if ($role !== 'system' && $actor->roles()->map(fn ($r) => $r->value)->contains($role)) {
                return;
            }
        }

        throw InvalidTransitionException::guardFailed($from, $to, 'peran aktor tidak berwenang.');
    }

    private function guard(SupervisionCycle $cycle, S $to, ?string $reason): void
    {
        $from = $cycle->status;

        // Pembatalan: alasan wajib; tidak boleh setelah tindak lanjut dimulai.
        if ($to === S::Canceled) {
            if ($reason === null || trim($reason) === '') {
                throw InvalidTransitionException::guardFailed($from, $to, 'alasan pembatalan wajib diisi.');
            }

            return;
        }

        match ([$from->value, $to->value]) {
            [S::Draft->value, S::Scheduled->value] => $this->guardSchedule($cycle),
            [S::Scheduled->value, S::ObservationDone->value] => $this->guardFinalizeObservation($cycle),
            default => $this->guardPhase3($from, $to),
        };
    }

    private function guardSchedule(SupervisionCycle $cycle): void
    {
        $agreement = $cycle->planningAgreement()->first();

        if ($agreement === null) {
            throw InvalidTransitionException::guardFailed(S::Draft, S::Scheduled, 'kesepakatan pra-observasi belum dibuat.');
        }

        if ($agreement->disepakati_guru_at === null || $agreement->disepakati_supervisor_at === null) {
            throw InvalidTransitionException::guardFailed(S::Draft, S::Scheduled, 'kesepakatan belum ditandatangani kedua pihak.');
        }
    }

    private function guardFinalizeObservation(SupervisionCycle $cycle): void
    {
        $hasFinal = $cycle->observations()->where('status', 'final')->exists();

        if (! $hasFinal) {
            throw InvalidTransitionException::guardFailed(S::Scheduled, S::ObservationDone, 'belum ada observasi berstatus final.');
        }
    }

    private function guardPhase3(S $from, S $to): void
    {
        throw InvalidTransitionException::guardFailed(
            $from,
            $to,
            'modul tahap pasca-observasi (analisis/umpan balik/tindak lanjut/pelaporan) hadir di Fase 3.',
        );
    }
}
