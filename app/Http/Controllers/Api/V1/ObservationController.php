<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Observation\Actions\FinalizeObservation;
use App\Domain\Observation\Actions\SaveObservation;
use App\Domain\Observation\Actions\StartObservation;
use App\Domain\Observation\Exceptions\ObservationConflictException;
use App\Http\Requests\Api\SaveObservationRequest;
use App\Http\Requests\Api\StartObservationRequest;
use App\Http\Requests\Api\UploadObservationMediaRequest;
use App\Http\Resources\ObservationResource;
use App\Models\Observation;
use App\Models\ObservationMedia;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObservationController extends ApiController
{
    public function store(StartObservationRequest $request, SupervisionCycle $cycle, StartObservation $action): JsonResponse
    {
        $this->authorize('observe', $cycle);

        try {
            $observation = $action->handle(
                $this->user($request),
                $cycle,
                $request->string('id')->toString() ?: null,
                $request->string('device_id')->toString() ?: null,
            );
        } catch (DomainException $e) {
            return $this->fail(['observation' => [$e->getMessage()]]);
        }

        return $this->ok(
            ObservationResource::make($observation)->resolve(),
            ['version' => $observation->version],
            201,
        );
    }

    public function update(SaveObservationRequest $request, Observation $observation, SaveObservation $action): JsonResponse
    {
        $this->authorize('update', $observation);

        try {
            $observation = $action->handle(
                $observation,
                $request->responsePayload(),
                $request->metaPayload(),
                $request->has('base_version') ? $request->integer('base_version') : null,
            );
        } catch (ObservationConflictException $e) {
            return $this->fail(
                ['conflict' => ["server_version {$e->serverObservation->version} != client_base {$e->clientBaseVersion}"]],
                409,
                ['data' => ['server' => ObservationResource::make($e->serverObservation->load('responses'))->resolve()]],
            );
        } catch (DomainException $e) {
            return $this->fail(['observation' => [$e->getMessage()]]);
        }

        return $this->ok(ObservationResource::make($observation)->resolve(), ['version' => $observation->version]);
    }

    public function finalize(Request $request, Observation $observation, FinalizeObservation $action): JsonResponse
    {
        $this->authorize('finalize', $observation);

        try {
            $result = $action->handle($this->user($request), $observation);
        } catch (DomainException $e) {
            return $this->fail(['finalize' => [$e->getMessage()]]);
        }

        return $this->ok(ObservationResource::make($result['observation'])->resolve(), ['version' => $result['observation']->version]);
    }

    public function media(UploadObservationMediaRequest $request, Observation $observation): JsonResponse
    {
        $this->authorize('uploadMedia', $observation);

        $file = $request->file('file');
        $path = $file->store("observations/{$observation->getKey()}", 'observation_media');

        $media = ObservationMedia::create([
            'observation_id' => $observation->getKey(),
            'tipe' => $request->string('tipe')->toString(),
            'disk' => 'observation_media',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'upload_status' => 'stored',
            'captured_at' => now(),
        ]);

        return $this->ok(['id' => $media->id, 'upload_status' => $media->upload_status], status: 201);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
