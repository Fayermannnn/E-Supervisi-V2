<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Feedback\Actions\AcknowledgeFeedback;
use App\Domain\Feedback\Actions\PostFeedbackMessage;
use App\Domain\Feedback\Actions\StartFeedbackSession;
use App\Models\FeedbackSession;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FeedbackController extends ApiController
{
    /**
     * POST /cycles/{cycle}/feedback — merekam sesi / pesan umpan balik.
     */
    public function store(Request $request, SupervisionCycle $cycle, StartFeedbackSession $start, PostFeedbackMessage $post): JsonResponse
    {
        $this->authorize('view', $cycle);
        $actor = $this->user($request);

        $data = $request->validate([
            'tipe' => ['required', 'in:observasi,pertanyaan_reflektif,tanggapan,kesepakatan'],
            'konten' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $session = $start->handle($cycle);
            $this->authorize('record', $session);
            $message = $post->handle($actor, $session, $data['tipe'], $data['konten']);
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['feedback' => [$e->getMessage()]]);
        }

        return $this->ok(['id' => $message->id, 'session_id' => $message->feedback_session_id], status: 201);
    }

    /**
     * PATCH /cycles/{cycle}/feedback/ack — guru konfirmasi penerimaan.
     */
    public function acknowledge(Request $request, SupervisionCycle $cycle, AcknowledgeFeedback $action): JsonResponse
    {
        $session = FeedbackSession::where('cycle_id', $cycle->id)->firstOrFail();
        $this->authorize('acknowledge', $session);

        try {
            $session = $action->handle($this->user($request), $session);
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['feedback' => [$e->getMessage()]]);
        }

        return $this->ok([
            'status_konfirmasi_guru' => $session->status_konfirmasi_guru,
            'cycle_status' => $cycle->refresh()->status->value,
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
