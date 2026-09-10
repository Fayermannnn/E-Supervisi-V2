<?php

declare(strict_types=1);

namespace App\Domain\Analysis\Actions;

use App\Domain\Analysis\SchemaDrivenScorer;
use App\Domain\Audit\AuditLogger;
use App\Domain\Instruments\InstrumentSchema;
use App\Models\AnalysisFinding;
use App\Models\AnalysisResult;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung skor & menyiapkan draf analisis manual untuk siklus (M3).
 * Menghasilkan baseline temuan (kekuatan / area pengembangan) dari skor seksi.
 */
class PerformAnalysis
{
    public function __construct(
        private readonly SchemaDrivenScorer $scorer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @throws DomainException
     */
    public function handle(User $actor, SupervisionCycle $cycle): AnalysisResult
    {
        if (! in_array($cycle->status, [CycleStatus::ObservationDone, CycleStatus::AnalysisDone], true)) {
            throw new DomainException('Analisis hanya dapat disusun setelah observasi selesai.');
        }

        $observation = $cycle->observations()->where('status', 'final')->latest('finalized_at')->first();
        if ($observation === null) {
            throw new DomainException('Belum ada observasi final untuk dianalisis.');
        }

        $summary = $this->scorer->score($observation);
        $schema = InstrumentSchema::fromArray($observation->instrumentVersion()->sole()->schema_json);

        return DB::transaction(function () use ($actor, $cycle, $observation, $summary, $schema): AnalysisResult {
            $result = AnalysisResult::updateOrCreate(
                ['cycle_id' => $cycle->getKey()],
                [
                    'observation_id' => $observation->getKey(),
                    'score_summary' => $summary,
                    'sumber' => AnalysisResult::SUMBER_MANUAL,
                    'status_review' => AnalysisResult::REVIEW_DRAFT,
                ],
            );

            // Regenerasi baseline findings (hanya yang belum disunting manual).
            $result->findings()->where('kategori', '!=', AnalysisFinding::KATEGORI_POLA)->delete();

            $ordered = collect($summary['sections'])
                ->map(fn (array $s, string $key): array => ['key' => $key, 'score' => $s['score']])
                ->filter(fn (array $s) => $s['score'] !== null)
                ->sortByDesc('score')
                ->values();

            $urutan = 0;
            foreach ($ordered->take(2) as $section) {
                $result->findings()->create([
                    'kategori' => AnalysisFinding::KATEGORI_KEKUATAN,
                    'deskripsi' => $this->sectionLabel($schema, $section['key']).' relatif kuat (skor '.number_format((float) $section['score'] * 100, 0).'%).',
                    'bukti_ref' => ['section' => $section['key']],
                    'prioritas' => 3,
                    'urutan' => $urutan++,
                ]);
            }
            foreach ($ordered->reverse()->take(2) as $section) {
                $result->findings()->create([
                    'kategori' => AnalysisFinding::KATEGORI_PENGEMBANGAN,
                    'deskripsi' => $this->sectionLabel($schema, $section['key']).' perlu penguatan (skor '.number_format((float) $section['score'] * 100, 0).'%).',
                    'bukti_ref' => ['section' => $section['key']],
                    'prioritas' => 2,
                    'urutan' => $urutan++,
                ]);
            }

            $this->audit->log('analysis.performed', $cycle, new: ['total' => $summary['total']], actor: $actor);

            return $result->refresh();
        });
    }

    private function sectionLabel(InstrumentSchema $schema, string $key): string
    {
        foreach ($schema->sections as $section) {
            if ($section->key === $key) {
                return $section->title;
            }
        }

        return $key;
    }
}
