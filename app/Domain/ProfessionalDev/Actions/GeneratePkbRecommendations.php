<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Actions;

use App\Domain\Administration\PolicySettings;
use App\Domain\Audit\AuditLogger;
use App\Domain\ProfessionalDev\PkbMatcher;
use App\Models\PkbCatalogItem;
use App\Models\PkbRecommendation;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun rekomendasi PKB deterministik dari area pengembangan pada analisis
 * final siklus, ditandai `rtl_berulang` bila polanya berulang lintas siklus
 * guru (M9, @provisional). Tidak memakai AI. Hasil = draf untuk ditinjau guru.
 *
 * Membaca data domain Analysis lewat query tabel (bukan import model) agar arah
 * ketergantungan domain-map terjaga.
 */
class GeneratePkbRecommendations
{
    public function __construct(
        private readonly PolicySettings $policy,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{dibuat: int, area: list<string>}
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, SupervisionCycle $cycle): array
    {
        if (! $actor->isSupervisor() || $cycle->supervisor_id !== $actor->getKey()) {
            throw new AuthorizationException('Hanya supervisor siklus yang dapat menyusun rekomendasi PKB.');
        }

        $analysis = DB::table('analysis_results')
            ->where('cycle_id', $cycle->getKey())
            ->where('status_review', 'final')
            ->first();

        if ($analysis === null) {
            throw new DomainException('Rekomendasi PKB hanya dapat disusun setelah analisis final.');
        }

        $currentAreas = $this->developmentAreas($cycle->getKey());
        if ($currentAreas === []) {
            throw new DomainException('Tidak ada area pengembangan pada analisis siklus ini.');
        }

        $keywords = [];
        foreach ($currentAreas as $text) {
            $keywords = array_merge($keywords, PkbMatcher::keywords($text));
        }
        $keywords = array_values(array_unique($keywords));

        $recurring = $this->recurringKeywords($cycle, $keywords);

        $items = PkbCatalogItem::query()
            ->availableFor($cycle->dinas_id)
            ->get()
            ->map(static fn (PkbCatalogItem $i): array => ['id' => $i->getKey(), 'tags' => $i->tags ?? []])
            ->all();

        $matches = PkbMatcher::match($keywords, $items);

        $dibuat = 0;
        foreach ($matches as $itemId => $matchedTags) {
            $existing = PkbRecommendation::query()
                ->where('cycle_id', $cycle->getKey())
                ->where('pkb_catalog_item_id', $itemId)
                ->first();

            if ($existing !== null && $existing->status !== PkbRecommendation::STATUS_DISARANKAN) {
                continue; // jangan timpa keputusan guru
            }

            $isRecurring = false;
            foreach ($matchedTags as $tag) {
                foreach (PkbMatcher::keywords($tag) as $w) {
                    if (in_array($w, $recurring, true)) {
                        $isRecurring = true;
                        break 2;
                    }
                }
            }

            PkbRecommendation::updateOrCreate(
                ['cycle_id' => $cycle->getKey(), 'pkb_catalog_item_id' => $itemId],
                [
                    'guru_id' => $cycle->guru_id,
                    'sumber' => $isRecurring ? PkbRecommendation::SUMBER_RTL_BERULANG : PkbRecommendation::SUMBER_ANALISIS,
                    'alasan' => 'Relevan dengan area pengembangan: '.implode(', ', $matchedTags).'.'
                        .($isRecurring ? ' Pola ini berulang pada siklus sebelumnya.' : ''),
                    'status' => PkbRecommendation::STATUS_DISARANKAN,
                    'direkomendasikan_oleh' => $actor->getKey(),
                ],
            );
            $dibuat++;
        }

        $this->audit->log('pkb.recommendations_generated', $cycle, new: ['dibuat' => $dibuat], actor: $actor);

        return ['dibuat' => $dibuat, 'area' => $currentAreas];
    }

    /**
     * @return list<string>
     */
    private function developmentAreas(string $cycleId): array
    {
        $rows = DB::table('analysis_findings')
            ->join('analysis_results', 'analysis_findings.analysis_result_id', '=', 'analysis_results.id')
            ->where('analysis_results.cycle_id', $cycleId)
            ->where('analysis_findings.kategori', 'area_pengembangan')
            ->pluck('analysis_findings.deskripsi')
            ->all();

        return array_values(array_map(static fn ($d): string => (string) $d, $rows));
    }

    /**
     * Kata kunci yang muncul pada ≥ ambang siklus guru yang berbeda (termasuk siklus ini).
     *
     * @param  list<string>  $keywords
     * @return list<string>
     */
    private function recurringKeywords(SupervisionCycle $cycle, array $keywords): array
    {
        $threshold = (int) $this->policy->get('professional_dev.pkb_recurrence_threshold', $cycle->dinas_id);

        $rows = DB::table('analysis_findings')
            ->join('analysis_results', 'analysis_findings.analysis_result_id', '=', 'analysis_results.id')
            ->join('supervision_cycles', 'analysis_results.cycle_id', '=', 'supervision_cycles.id')
            ->where('supervision_cycles.guru_id', $cycle->guru_id)
            ->where('analysis_findings.kategori', 'area_pengembangan')
            ->select('analysis_results.cycle_id', 'analysis_findings.deskripsi')
            ->get();

        /** @var array<string, array<string, bool>> $cyclesByKeyword */
        $cyclesByKeyword = [];
        foreach ($rows as $row) {
            foreach (PkbMatcher::keywords((string) $row->deskripsi) as $word) {
                if (! in_array($word, $keywords, true)) {
                    continue;
                }
                $cyclesByKeyword[$word][(string) $row->cycle_id] = true;
            }
        }

        $recurring = [];
        foreach ($cyclesByKeyword as $word => $cycles) {
            if (count($cycles) >= $threshold) {
                $recurring[] = $word;
            }
        }

        return $recurring;
    }
}
