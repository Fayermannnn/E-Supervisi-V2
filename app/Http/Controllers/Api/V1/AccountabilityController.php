<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accountability\AccountabilityAggregator;
use App\Domain\Accountability\Actions\AddCalibrationParticipant;
use App\Domain\Accountability\Actions\CloseCalibrationSession;
use App\Domain\Accountability\Actions\CreateCalibrationSession;
use App\Domain\Accountability\Actions\SubmitCalibrationScores;
use App\Domain\Accountability\Actions\SubmitSupervisorEvaluation;
use App\Http\Requests\Api\SubmitSupervisorEvaluationRequest;
use App\Models\CalibrationSession;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Akuntabilitas Supervisor 360° (M11) & Kalibrasi Antar-Penilai (M12).
 *
 * @provisional.
 */
class AccountabilityController extends ApiController
{
    public function storeEvaluation(SubmitSupervisorEvaluationRequest $request, SupervisionCycle $cycle, SubmitSupervisorEvaluation $action): JsonResponse
    {
        try {
            $evaluation = $action->handle($this->user($request), $cycle, $request->answers(), $request->input('komentar'));
        } catch (AuthorizationException $e) {
            return $this->fail(['evaluation' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['evaluation' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $evaluation->id, 'submitted_at' => $evaluation->submitted_at], status: 201);
    }

    public function aggregate(Request $request, AccountabilityAggregator $aggregator): JsonResponse
    {
        $user = $this->user($request);

        if ($user->isAdminDinas() && $user->adminDinasId() !== null) {
            return $this->ok($aggregator->forDinas($user->adminDinasId()));
        }

        if ($user->isSupervisor()) {
            return $this->ok($aggregator->forSupervisor($user));
        }

        return $this->fail(['aggregate' => ['Tidak tersedia untuk peran Anda.']], status: 403);
    }

    public function createCalibration(Request $request, CreateCalibrationSession $action): JsonResponse
    {
        $data = $request->validate([
            'instrument_version_id' => ['required', 'uuid', 'exists:instrument_versions,id'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'observation_id' => ['nullable', 'uuid', 'exists:observations,id'],
            'artefak_url' => ['nullable', 'url', 'max:2000'],
            'dinas_id' => ['nullable', 'uuid', 'exists:dinas,id'],
        ]);

        try {
            $session = $action->handle($this->user($request), $data);
        } catch (AuthorizationException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $session->id], status: 201);
    }

    public function addParticipant(Request $request, CalibrationSession $session, AddCalibrationParticipant $action): JsonResponse
    {
        $data = $request->validate(['supervisor_id' => ['required', 'uuid', 'exists:users,id']]);
        $supervisor = User::query()->findOrFail((string) $data['supervisor_id']);

        try {
            $participant = $action->handle($this->user($request), $session, $supervisor);
        } catch (AuthorizationException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $participant->id], status: 201);
    }

    public function submitScores(Request $request, CalibrationSession $session, SubmitCalibrationScores $action): JsonResponse
    {
        $data = $request->validate([
            'scores' => ['required', 'array', 'min:1'],
            'scores.*' => ['numeric'],
        ]);

        try {
            $action->handle($this->user($request), $session, $data['scores']);
        } catch (AuthorizationException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]]);
        }

        return $this->ok(['submitted' => true]);
    }

    public function closeCalibration(Request $request, CalibrationSession $session, CloseCalibrationSession $action): JsonResponse
    {
        try {
            $session = $action->handle($this->user($request), $session);
        } catch (AuthorizationException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]], status: 403);
        } catch (DomainException $e) {
            return $this->fail(['calibration' => [$e->getMessage()]]);
        }

        return $this->ok(['status' => $session->status, 'stats' => $session->stats]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
