<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Actions;

use App\Domain\Ai\Actions\GenerateAiDraft;
use App\Models\AnalysisResult;
use App\Models\User;

/**
 * Meminta draf ringkasan analisis dari Asisten AI (M18 tahap 1).
 * Konteks dibangun dari data yang SUDAH lolos otorisasi pemanggil — AI tidak
 * mengakses DB sendiri.
 */
class RequestAnalysisAiDraft
{
    public function __construct(private readonly GenerateAiDraft $generate) {}

    public function handle(User $supervisor, AnalysisResult $result): \App\Models\AiGeneration
    {
        $summary = $result->score_summary ?? [];

        $strengths = $result->findings()
            ->where('kategori', 'kekuatan')->pluck('deskripsi')->all();
        $growth = $result->findings()
            ->where('kategori', 'area_pengembangan')->pluck('deskripsi')->all();

        $context = [
            'score_summary' => [
                'total' => $summary['total'] ?? null,
                'band' => $summary['band'] ?? null,
                'sections' => $summary['sections'] ?? [],
            ],
            'strengths' => $strengths,
            'growth_areas' => $growth,
            'catatan_skrip' => $result->cycle()->sole()
                ->observations()->where('status', 'final')->latest('finalized_at')->value('catatan_skrip'),
        ];

        $generation = $this->generate->handle(
            $supervisor,
            AnalysisResult::class,
            $result->getKey(),
            'analysis_summary',
            $context,
        );

        $result->forceFill(['ai_generation_id' => $generation->id])->save();

        return $generation;
    }
}
