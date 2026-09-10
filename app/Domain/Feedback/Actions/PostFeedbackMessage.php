<?php

declare(strict_types=1);

namespace App\Domain\Feedback\Actions;

use App\Models\AiGeneration;
use App\Models\FeedbackMessage;
use App\Models\FeedbackSession;
use App\Models\User;
use DomainException;

class PostFeedbackMessage
{
    /**
     * @param  string  $tipe  observasi | pertanyaan_reflektif | tanggapan | kesepakatan
     *
     * @throws DomainException
     */
    public function handle(User $sender, FeedbackSession $session, string $tipe, string $konten, ?AiGeneration $fromAi = null): FeedbackMessage
    {
        if ($session->status === FeedbackSession::STATUS_SELESAI) {
            throw new DomainException('Sesi umpan balik sudah ditutup.');
        }

        $cycle = $session->cycle()->sole();
        $peran = $cycle->supervisor_id === $sender->getKey() ? 'supervisor'
            : ($cycle->guru_id === $sender->getKey() ? 'guru' : null);

        if ($peran === null) {
            throw new DomainException('Anda bukan pihak dalam sesi ini.');
        }

        // Pesan dari draf AI hanya boleh dikirim setelah ditinjau supervisor (RULE 4).
        if ($fromAi !== null && ! $fromAi->isHumanApproved()) {
            throw new DomainException('Saran AI harus ditinjau & disunting sebelum dikirim ke guru.');
        }

        $next = (int) $session->messages()->max('urutan') + 1;

        return FeedbackMessage::create([
            'feedback_session_id' => $session->getKey(),
            'pengirim_id' => $sender->getKey(),
            'peran' => $peran,
            'tipe' => $tipe,
            'konten' => $konten,
            'urutan' => $next,
            'ai_generation_id' => $fromAi?->getKey(),
            'created_at' => now(),
        ]);
    }
}
