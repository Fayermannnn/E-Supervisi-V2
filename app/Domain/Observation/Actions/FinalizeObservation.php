<?php

declare(strict_types=1);

namespace App\Domain\Observation\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Observation\Enums\ObservationStatus as OS;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\Observation;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

class FinalizeObservation
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CycleStateMachine $stateMachine,
    ) {}

    /**
     * @return array{observation: Observation, missing: list<string>}
     *
     * @throws DomainException bila ada item wajib belum terisi
     */
    public function handle(User $actor, Observation $observation): array
    {
        if ($observation->isFinal()) {
            return ['observation' => $observation, 'missing' => []];
        }

        $schema = $observation->instrumentVersion()->sole()->schema();

        /** @var list<string> $answered */
        $answered = $observation->responses()->pluck('item_key')->all();

        $missing = array_values(array_diff($schema->requiredItemKeys(), $answered));

        if ($missing !== []) {
            throw new DomainException('Masih ada '.count($missing).' item wajib yang belum dinilai.');
        }

        $cycle = $observation->cycle()->sole();

        DB::transaction(function () use ($actor, $observation, $cycle): void {
            $observation->forceFill([
                'status' => OS::Final->value,
                'finalized_at' => now(),
                'selesai_at' => $observation->selesai_at ?? now(),
                'version' => $observation->version + 1,
            ])->save();

            $this->audit->log('observation.finalized', $cycle, context: ['observation_id' => $observation->getKey()], actor: $actor);

            if ($cycle->status === CycleStatus::Scheduled) {
                $this->stateMachine->transition($cycle, CycleStatus::ObservationDone, $actor);
            }
        });

        return ['observation' => $observation->refresh(), 'missing' => []];
    }
}
