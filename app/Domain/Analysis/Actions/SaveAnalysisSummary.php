<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\AiGeneration;
use App\Models\AnalysisResult;
use App\Models\User;
use DomainException;

/**
 * Menyimpan ringkasan analisis yang disunting supervisor. Bila berasal dari
 * draf AI yang sudah ditinjau, sumber ditandai `ai_edited` — bukan `ai_draft`,
 * sehingga finalisasi diperbolehkan (human-in-the-loop terpenuhi).
 */
class SaveAnalysisSummary
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $actor, AnalysisResult $result, string $ringkasan, ?AiGeneration $fromGeneration = null): AnalysisResult
    {
        if ($result->isFinal()) {
            throw new DomainException('Analisis sudah final.');
        }

        $sumber = $result->sumber;
        if ($fromGeneration !== null && $fromGeneration->isHumanApproved()) {
            $sumber = AnalysisResult::SUMBER_AI_EDITED;
        } elseif ($fromGeneration !== null) {
            $sumber = AnalysisResult::SUMBER_AI_DRAFT; // ditolak saat finalisasi
        } elseif ($result->sumber === AnalysisResult::SUMBER_MANUAL) {
            $sumber = AnalysisResult::SUMBER_MANUAL;
        }

        $result->forceFill([
            'ringkasan' => $ringkasan,
            'sumber' => $sumber,
            'status_review' => AnalysisResult::REVIEW_IN_REVIEW,
        ])->save();

        $this->audit->log('analysis.summary_saved', $result->cycle()->sole(), context: ['sumber' => $sumber], actor: $actor);

        return $result->refresh();
    }
}
