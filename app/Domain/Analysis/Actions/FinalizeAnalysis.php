<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\AnalysisResult;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Mengunci analisis final — WAJIB oleh manusia (RULE 4, Spec §6.6/§11).
 * Menolak bila ringkasan bersumber AI mentah yang belum ditinjau.
 */
class FinalizeAnalysis
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CycleStateMachine $stateMachine,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $reviewer, AnalysisResult $result): AnalysisResult
    {
        if (! $reviewer->can(Permission::FinalizeAnalysis->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengunci analisis.');
        }

        if ($result->isFinal()) {
            return $result;
        }

        if (trim((string) $result->ringkasan) === '') {
            throw new DomainException('Ringkasan analisis belum diisi.');
        }

        if ($result->sumber === AnalysisResult::SUMBER_AI_DRAFT) {
            throw new DomainException('Ringkasan masih berupa draf AI mentah. Tinjau & sunting terlebih dahulu.');
        }

        $cycle = $result->cycle()->sole();

        return DB::transaction(function () use ($reviewer, $result, $cycle): AnalysisResult {
            $result->forceFill([
                'status_review' => AnalysisResult::REVIEW_FINAL,
                'reviewed_by' => $reviewer->getKey(),
                'finalized_at' => now(),
            ])->save();

            $this->audit->log('analysis.finalized', $cycle, context: ['sumber' => $result->sumber], actor: $reviewer);

            if ($cycle->status === CycleStatus::ObservationDone) {
                $this->stateMachine->transition($cycle, CycleStatus::AnalysisDone, $reviewer);
            }

            return $result->refresh();
        });
    }
}
