<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Planning\Actions\RecordPlanningAgreementConsent;
use App\Domain\Planning\Actions\SavePlanningAgreement;
use App\Domain\Planning\Actions\SubmitReflection;
use App\Domain\Supervision\Actions\CancelCycle;
use App\Domain\Supervision\Actions\CreateCycle;
use App\Http\Requests\Api\SaveAgreementRequest;
use App\Http\Requests\Api\StoreCycleRequest;
use App\Http\Requests\Api\SubmitReflectionRequest;
use App\Http\Resources\CycleResource;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CycleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $this->authorize('viewAny', SupervisionCycle::class);

        $cycles = SupervisionCycle::query()
            ->visibleTo($user)
            ->with(['guru', 'supervisor'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->integer('status')))
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return $this->ok(
            CycleResource::collection($cycles)->resolve(),
            ['page' => $cycles->currentPage(), 'total' => $cycles->total()],
        );
    }

    public function store(StoreCycleRequest $request, CreateCycle $action): JsonResponse
    {
        $supervisor = $this->user($request);
        $this->authorize('create', SupervisionCycle::class);

        $guru = User::query()->whereKey($request->string('guru_id'))->firstOrFail();

        try {
            $cycle = $action->handle(
                $supervisor,
                $guru,
                $request->string('tahun_ajaran')->toString(),
                $request->string('semester')->toString(),
                $request->string('judul')->toString(),
                $request->string('fokus_ringkas')->toString() ?: null,
            );
        } catch (DomainException $e) {
            return $this->fail(['guru_id' => [$e->getMessage()]]);
        }

        return $this->ok(CycleResource::make($cycle)->resolve(), status: 201);
    }

    public function show(Request $request, SupervisionCycle $cycle): JsonResponse
    {
        $this->authorize('view', $cycle);

        $cycle->load(['guru', 'supervisor', 'planningAgreement']);

        return $this->ok(CycleResource::make($cycle)->resolve());
    }

    public function schedule(SaveAgreementRequest $request, SupervisionCycle $cycle, SavePlanningAgreement $save, RecordPlanningAgreementConsent $consent): JsonResponse
    {
        $this->authorize('schedule', $cycle);
        $actor = $this->user($request);

        try {
            $save->handle($actor, $cycle, $request->agreementData());

            if ($request->boolean('consent')) {
                $cycle = $consent->handle($actor, $cycle);
            }
        } catch (DomainException $e) {
            return $this->fail(['agreement' => [$e->getMessage()]]);
        }

        return $this->ok(CycleResource::make($cycle->refresh()->load('planningAgreement'))->resolve());
    }

    public function cancel(Request $request, SupervisionCycle $cycle, CancelCycle $action): JsonResponse
    {
        $this->authorize('cancel', $cycle);
        $reason = (string) $request->string('reason');

        try {
            $cycle = $action->handle($this->user($request), $cycle, $reason);
        } catch (RuntimeException $e) {
            return $this->fail(['reason' => [$e->getMessage()]]);
        }

        return $this->ok(CycleResource::make($cycle)->resolve());
    }

    public function reflections(SubmitReflectionRequest $request, SupervisionCycle $cycle, SubmitReflection $action): JsonResponse
    {
        $this->authorize('submitReflection', $cycle);

        $reflection = $action->handle(
            $this->user($request),
            $cycle,
            $request->string('tahap')->toString(),
            $request->string('konten')->toString(),
        );

        return $this->ok(['id' => $reflection->id, 'submitted_at' => $reflection->submitted_at?->toIso8601String()], status: 201);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
