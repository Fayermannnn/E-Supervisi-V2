<?php

declare(strict_types=1);

namespace App\Domain\Observation\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\Observation;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Support\Str;

class StartObservation
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws DomainException
     */
    public function handle(
        User $observer,
        SupervisionCycle $cycle,
        ?string $observationId = null,
        ?string $deviceId = null,
    ): Observation {
        if ($cycle->status !== CycleStatus::Scheduled) {
            throw new DomainException('Observasi hanya dapat dimulai saat siklus berstatus Terjadwal.');
        }

        $agreement = $cycle->planningAgreement()->first();

        if ($agreement === null) {
            throw new DomainException('Kesepakatan pra-observasi belum lengkap.');
        }

        $id = $observationId ?? (string) Str::uuid7();

        $existing = Observation::query()->whereKey($id)->first();
        if ($existing !== null) {
            return $existing; // idempoten: klien mengirim ulang UUID yang sama
        }

        $observation = Observation::create([
            'id' => $id,
            'cycle_id' => $cycle->getKey(),
            'observer_id' => $observer->getKey(),
            'instrument_version_id' => $agreement->instrument_version_id,
            'tipe' => $agreement->tipe_observasi->value,
            'mulai_at' => now(),
            'device_id' => $deviceId,
            'client_updated_at' => now(),
        ]);

        $this->audit->log('observation.started', $cycle, context: ['observation_id' => $id], actor: $observer);

        return $observation;
    }
}
