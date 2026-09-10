<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Evaluation\ExpertJudgmentInstrument;
use App\Models\EvaluationPanel;
use App\Models\ExpertReview;
use App\Models\PanelExpert;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Ahli mengirim / memperbarui penilaiannya atas artefak. Dapat disunting
 * selama panel masih `berjalan`.
 */
class SubmitExpertReview
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $jawaban
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $expert, PanelExpert $panelExpert, array $jawaban, ?string $catatan = null): ExpertReview
    {
        if (! $expert->can(Permission::SubmitExpertReview->value) || $panelExpert->user_id !== $expert->getKey()) {
            throw new AuthorizationException('Anda bukan ahli yang ditugaskan pada panel ini.');
        }

        $panel = $panelExpert->panel()->sole();
        if ($panel->status !== EvaluationPanel::STATUS_BERJALAN) {
            throw new DomainException('Panel tidak sedang menerima penilaian.');
        }

        if (! ExpertJudgmentInstrument::isComplete($jawaban)) {
            throw new DomainException('Seluruh aspek relevansi, kualitas, dan 10 butir usability wajib diisi.');
        }

        $review = ExpertReview::updateOrCreate(
            ['panel_expert_id' => $panelExpert->getKey()],
            [
                'status' => ExpertReview::STATUS_TERKIRIM,
                'jawaban' => ExpertJudgmentInstrument::normalize($jawaban),
                'catatan' => $catatan !== null && trim($catatan) !== '' ? trim($catatan) : null,
                'submitted_at' => now(),
            ],
        );

        $this->audit->log('evaluation.review_submitted', $panel, new: ['panel_expert_id' => $panelExpert->getKey()], actor: $expert);

        return $review->refresh();
    }
}
