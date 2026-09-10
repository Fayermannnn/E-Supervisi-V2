<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Evaluation\ExpertJudgmentStats;
use App\Models\EvaluationPanel;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Menutup panel evaluasi, menghitung dan menyimpan snapshot CVR/CVI, Aiken's V,
 * dan SUS dari seluruh penilaian ahli yang telah dikirim.
 */
class CloseEvaluationPanel
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, EvaluationPanel $panel): EvaluationPanel
    {
        if (! $actor->can(Permission::ManageEvaluationPanel->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengelola panel evaluasi.');
        }

        if ($panel->status === EvaluationPanel::STATUS_SELESAI) {
            return $panel;
        }

        $reviews = [];
        $rumpun = [];
        foreach ($panel->experts()->with('review')->get() as $panelExpert) {
            $review = $panelExpert->review()->first();
            if ($review === null || ! $review->isSubmitted()) {
                continue;
            }
            $reviews[] = $review->jawaban;
            $rumpun[] = $panelExpert->rumpun;
        }

        if ($reviews === []) {
            throw new DomainException('Belum ada penilaian ahli yang dikirim.');
        }

        $stats = ExpertJudgmentStats::compute($reviews, $rumpun);

        $panel->forceFill([
            'status' => EvaluationPanel::STATUS_SELESAI,
            'stats' => $stats,
            'closed_at' => now(),
        ])->save();

        $this->audit->log('evaluation.panel_closed', $panel, new: [
            'n_ahli' => $stats['n_ahli'],
            'cvi' => $stats['cvi'],
            'aiken_v_rata' => $stats['aiken_v_rata'],
            'sus_rata' => $stats['sus']['rata'],
        ], actor: $actor);

        return $panel->refresh();
    }
}
