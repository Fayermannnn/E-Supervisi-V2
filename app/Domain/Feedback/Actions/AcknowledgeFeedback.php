<?php

declare(strict_types=1);

namespace App\Domain\Feedback\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\FeedbackSession;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Guru mengonfirmasi penerimaan umpan balik. Ini gerbang transisi
 * ANALYSIS_DONE → FEEDBACK_GIVEN (Spec §5 status 4).
 */
class AcknowledgeFeedback
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CycleStateMachine $stateMachine,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $guru, FeedbackSession $session): FeedbackSession
    {
        if (! $guru->can(Permission::AcknowledgeFeedback->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengonfirmasi umpan balik.');
        }

        $cycle = $session->cycle()->sole();

        if ($cycle->guru_id !== $guru->getKey()) {
            throw new AuthorizationException('Umpan balik ini bukan untuk Anda.');
        }

        if ($session->messages()->count() === 0) {
            throw new DomainException('Belum ada isi umpan balik untuk dikonfirmasi.');
        }

        // Tidak boleh ada pesan bersumber AI yang belum ditinjau manusia.
        $unreviewedAi = $session->messages()
            ->whereNotNull('ai_generation_id')
            ->whereHas('aiGeneration', fn ($q) => $q->whereNotIn('review_status', ['accepted', 'edited']))
            ->exists();

        if ($unreviewedAi) {
            throw new DomainException('Masih ada saran AI yang belum ditinjau supervisor.');
        }

        return DB::transaction(function () use ($guru, $session, $cycle): FeedbackSession {
            $session->forceFill([
                'status' => FeedbackSession::STATUS_SELESAI,
                'status_konfirmasi_guru' => FeedbackSession::KONFIRMASI_DIKONFIRMASI,
                'dikonfirmasi_guru_at' => now(),
            ])->save();

            $this->audit->log('feedback.acknowledged', $cycle, actor: $guru);

            if ($cycle->status === CycleStatus::AnalysisDone) {
                $this->stateMachine->transition($cycle, CycleStatus::FeedbackGiven, $cycle->supervisor()->sole());
            }

            return $session->refresh();
        });
    }
}
