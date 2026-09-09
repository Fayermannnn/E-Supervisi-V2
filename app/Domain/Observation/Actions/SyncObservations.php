<?php

declare(strict_types=1);

namespace App\Domain\Observation\Actions;

use App\Domain\Observation\Exceptions\ObservationConflictException;
use App\Models\Observation;
use App\Models\ObservationSyncLog;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;

/**
 * Menerapkan batch observasi dari antrean luring PWA (ADR-006).
 *
 * Idempoten: `observations.id` = UUID klien; kirim ulang payload identik tidak
 * menghasilkan duplikat. Konflik (base_version tertinggal) tidak diterapkan —
 * dikembalikan agar klien menyelesaikannya manual.
 */
class SyncObservations
{
    public function __construct(
        private readonly StartObservation $start,
        private readonly SaveObservation $save,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $batch
     * @return array{applied: list<array{id: string, version: int}>, conflicts: list<array{id: string, server_version: int, client_base: int}>}
     */
    public function handle(User $observer, array $batch): array
    {
        $applied = [];
        $conflicts = [];

        foreach ($batch as $payload) {
            $id = (string) ($payload['id'] ?? '');
            $result = $this->applyOne($observer, $payload);

            if ($result instanceof ObservationConflictException) {
                $conflicts[] = [
                    'id' => $id,
                    'server_version' => $result->serverObservation->version,
                    'client_base' => $result->clientBaseVersion,
                ];

                continue;
            }

            $applied[] = ['id' => $id, 'version' => $result->version];
        }

        return ['applied' => $applied, 'conflicts' => $conflicts];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyOne(User $observer, array $payload): Observation|ObservationConflictException
    {
        $id = (string) ($payload['id'] ?? '');
        $baseVersion = isset($payload['base_version']) ? (int) $payload['base_version'] : null;
        $deviceId = isset($payload['device_id']) ? (string) $payload['device_id'] : null;
        $hash = hash('sha256', (string) json_encode($payload));

        $observation = Observation::query()->whereKey($id)->first();

        if ($observation === null) {
            $cycle = SupervisionCycle::query()->whereKey((string) ($payload['cycle_id'] ?? ''))->firstOrFail();

            if ($cycle->supervisor_id !== $observer->getKey()) {
                throw new DomainException('Siklus bukan milik Anda.');
            }

            $observation = $this->start->handle($observer, $cycle, $id, $deviceId);
        } elseif ($observation->observer_id !== $observer->getKey()) {
            throw new DomainException('Observasi bukan milik Anda.');
        }

        $priorLog = ObservationSyncLog::query()
            ->where('observation_id', $id)
            ->where('payload_hash', $hash)
            ->whereIn('action', ['push', 'conflict'])
            ->orderByDesc('created_at')
            ->first();

        if ($priorLog?->action === 'push') {
            return $observation;
        }
        if ($priorLog?->action === 'conflict' && $observation->version === $priorLog->server_version) {
            return new ObservationConflictException($observation, (int) $priorLog->client_version);
        }

        /** @var list<array<string, mixed>> $responses */
        $responses = is_array($payload['responses'] ?? null) ? array_values($payload['responses']) : [];

        try {
            $observation = $this->save->handle($observation, $responses, [
                'catatan_skrip' => $payload['catatan_skrip'] ?? null,
                'mulai_at' => $payload['mulai_at'] ?? null,
                'selesai_at' => $payload['selesai_at'] ?? null,
                'client_updated_at' => $payload['client_updated_at'] ?? null,
            ], $baseVersion);
        } catch (ObservationConflictException $e) {
            ObservationSyncLog::create([
                'observation_id' => $id,
                'device_id' => $deviceId,
                'action' => 'conflict',
                'client_version' => $baseVersion,
                'server_version' => $e->serverObservation->version,
                'resolved' => false,
                'payload_hash' => $hash,
            ]);

            return $e;
        }

        ObservationSyncLog::create([
            'observation_id' => $id,
            'device_id' => $deviceId,
            'action' => 'push',
            'client_version' => $baseVersion,
            'server_version' => $observation->version,
            'resolved' => true,
            'payload_hash' => $hash,
        ]);

        return $observation;
    }
}
