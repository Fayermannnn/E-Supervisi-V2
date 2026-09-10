<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analysis\Actions\FinalizeAnalysis;
use App\Domain\Analysis\Actions\PerformAnalysis;
use App\Domain\Analysis\Actions\RequestAnalysisAiDraft;
use App\Domain\Analysis\Actions\SaveAnalysisSummary;
use App\Models\AiGeneration;
use App\Models\AnalysisResult;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AnalysisController extends ApiController
{
    /**
     * GET /cycles/{cycle}/analysis/draft — draf analisis (Asisten AI).
     */
    public function draft(Request $request, SupervisionCycle $cycle, PerformAnalysis $perform, RequestAnalysisAiDraft $requestAi): JsonResponse
    {
        $this->authorize('view', $cycle);
        $actor = $this->user($request);

        $result = AnalysisResult::where('cycle_id', $cycle->id)->first();

        try {
            if ($result === null) {
                $result = $perform->handle($actor, $cycle);
            }
            $this->authorize('requestAiDraft', $result);

            if ($request->boolean('generate') && $result->ai_generation_id === null) {
                $requestAi->handle($actor, $result);
                $result->refresh();
            }
        } catch (DomainException $e) {
            return $this->fail(['analysis' => [$e->getMessage()]]);
        }

        $generation = $result->ai_generation_id === null ? null : AiGeneration::find($result->ai_generation_id);

        return $this->ok([
            'score_summary' => $result->score_summary,
            'findings' => $result->findings()->get(['kategori', 'deskripsi']),
            'sumber' => $result->sumber,
            'status_review' => $result->status_review,
            'ai' => $generation === null ? null : [
                'id' => $generation->id,
                'status' => $generation->status,
                'review_status' => $generation->review_status,
                'output' => $generation->status === 'draft' ? $generation->output : null,
            ],
        ]);
    }

    /**
     * POST /cycles/{cycle}/analysis — kunci hasil analisis final (manusia).
     */
    public function finalize(Request $request, SupervisionCycle $cycle, SaveAnalysisSummary $save, FinalizeAnalysis $finalize): JsonResponse
    {
        $this->authorize('view', $cycle);
        $actor = $this->user($request);

        $data = $request->validate(['ringkasan' => ['required', 'string', 'min:30', 'max:20000']]);

        $result = AnalysisResult::where('cycle_id', $cycle->id)->firstOrFail();
        $this->authorize('finalize', $result);

        try {
            $save->handle($actor, $result, $data['ringkasan'], $result->ai_generation_id === null ? null : AiGeneration::find($result->ai_generation_id));
            $result = $finalize->handle($actor, $result->refresh());
        } catch (DomainException|RuntimeException $e) {
            return $this->fail(['analysis' => [$e->getMessage()]]);
        }

        return $this->ok([
            'status_review' => $result->status_review,
            'finalized_at' => $result->finalized_at?->toIso8601String(),
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
