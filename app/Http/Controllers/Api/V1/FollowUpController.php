<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\FollowUp\Actions\CreateFollowUpPlan;
use App\Domain\FollowUp\Actions\SubmitFollowUpEvidence;
use App\Domain\FollowUp\Actions\UpdateFollowUpItem;
use App\Models\FollowUpItem;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FollowUpController extends ApiController
{
    /**
     * POST /cycles/{cycle}/follow-up — membuat RTL.
     */
    public function store(Request $request, SupervisionCycle $cycle, CreateFollowUpPlan $action): JsonResponse
    {
        $this->authorize('view', $cycle);

        $data = $request->validate([
            'tujuan' => ['required', 'string', 'max:2000'],
            'tenggat' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.deskripsi' => ['required', 'string', 'max:1000'],
            'items.*.indikator_keberhasilan' => ['required', 'string', 'max:1000'],
            'items.*.tenggat_item' => ['nullable', 'date'],
        ]);

        try {
            $plan = $action->handle($this->user($request), $cycle, $data['tujuan'], $data['tenggat'], array_values($data['items']));
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['follow_up' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $plan->id, 'cycle_status' => $cycle->refresh()->status->value], status: 201);
    }

    /**
     * PATCH /follow-up/{item} — update status butir RTL.
     */
    public function updateItem(Request $request, FollowUpItem $item, UpdateFollowUpItem $action): JsonResponse
    {
        $plan = $item->plan()->sole();
        $this->authorize('update', $plan);

        $data = $request->validate(['status' => ['required', 'in:belum,berjalan,selesai']]);

        try {
            $item = $action->handle($this->user($request), $item, $data['status']);
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['follow_up' => [$e->getMessage()]]);
        }

        return $this->ok(['status' => $item->status, 'plan_status' => $plan->refresh()->status]);
    }

    /**
     * PATCH /follow-up/{item}/evidence — guru mengunggah bukti pelaksanaan.
     */
    public function evidence(Request $request, FollowUpItem $item, SubmitFollowUpEvidence $action): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'uuid'],
            'tipe' => ['required', 'in:dokumen,foto,tautan,catatan'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url', 'max:2000'],
        ]);

        try {
            $evidence = $action->handle($this->user($request), $item, $data);
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['evidence' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $evidence->id, 'upload_status' => $evidence->upload_status], status: 201);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
