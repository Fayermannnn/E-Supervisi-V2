<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Observation\Actions\SyncObservations;
use App\Http\Requests\Api\SyncObservationsRequest;
use App\Http\Resources\ObservationResource;
use App\Models\Observation;
use App\Models\ObservationSyncLog;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint sinkronisasi PWA luring (ADR-006, docs/offline.md).
 */
class SyncController extends ApiController
{
    /**
     * Data yang dibutuhkan observer untuk bekerja luring: siklus terjadwal +
     * versi instrumen + observasi draft yang sudah ada.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $user = $this->user($request);

        $cycles = SupervisionCycle::query()
            ->where('supervisor_id', $user->getKey())
            ->where('status', CycleStatus::Scheduled->value)
            ->with(['guru', 'planningAgreement.instrumentVersion'])
            ->get();

        $payload = $cycles->map(function (SupervisionCycle $cycle): array {
            $version = $cycle->planningAgreement?->instrumentVersion;

            return [
                'cycle' => [
                    'id' => $cycle->id,
                    'judul' => $cycle->judul,
                    'guru' => $cycle->guru?->name,
                    'jadwal_mulai' => $cycle->planningAgreement?->jadwal_mulai?->toIso8601String(),
                    'tipe_observasi' => $cycle->planningAgreement?->tipe_observasi->value,
                ],
                'instrument_version' => $version === null ? null : [
                    'id' => $version->id,
                    'schema' => $version->schema_json,
                ],
                'observations' => Observation::query()
                    ->where('cycle_id', $cycle->id)
                    ->where('status', 'draft')
                    ->with('responses')
                    ->get()
                    ->map(fn (Observation $o) => ObservationResource::make($o)->resolve())
                    ->all(),
            ];
        });

        return $this->ok($payload->all(), ['synced_at' => now()->toIso8601String()]);
    }

    public function observations(SyncObservationsRequest $request, SyncObservations $action): JsonResponse
    {
        try {
            $result = $action->handle($this->user($request), $request->batch());
        } catch (DomainException $e) {
            return $this->fail(['authorization' => [$e->getMessage()]], 403);
        }

        return $this->ok([
            'applied' => $result['applied'],
            'conflicts' => $result['conflicts'],
        ], ['count' => count($result['applied'])]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $this->user($request);

        $logs = ObservationSyncLog::query()
            ->whereHas('observation', fn ($q) => $q->where('observer_id', $user->getKey()))
            ->latest('created_at')
            ->limit(100)
            ->get(['observation_id', 'action', 'server_version', 'resolved', 'created_at']);

        return $this->ok($logs->toArray());
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
