<?php

declare(strict_types=1);

namespace App\Domain\Analysis;

use App\Domain\Instruments\InstrumentSchema;
use App\Models\Observation;

/**
 * Menghitung skor hasil observasi dari `instrument_versions.scoring_config` +
 * `observation_responses` (M3). Deterministik — diuji unit.
 *
 * scoring_config yang didukung:
 *   { "method": "weighted_mean_normalized",
 *     "section_weights": { "<key>": <num> },
 *     "bands": [ { "min": 0.85, "label": "Sangat Baik" }, ... ] }
 */
class SchemaDrivenScorer
{
    /**
     * @return array{
     *     method: string,
     *     sections: array<string, array{score: float|null, answered: int, total: int}>,
     *     total: float|null,
     *     band: string|null,
     *     answered: int,
     *     required_total: int
     * }
     */
    public function score(Observation $observation): array
    {
        $version = $observation->instrumentVersion()->sole();
        $schema = InstrumentSchema::fromArray($version->schema_json);
        $config = $version->scoring_config ?? [];

        /** @var array<string, array{numeric: float|null, boolean: bool|null}> $responseByItem */
        $responseByItem = [];
        foreach ($observation->responses()->get() as $r) {
            $responseByItem[$r->item_key] = [
                'numeric' => $r->value_numeric,
                'boolean' => $r->value_boolean,
            ];
        }

        $sections = [];
        $weightedSum = 0.0;
        $weightUsed = 0.0;
        $answered = 0;
        $requiredTotal = 0;

        foreach ($schema->sections as $section) {
            $scores = [];
            foreach ($section->items as $item) {
                if ($item->required) {
                    $requiredTotal++;
                }
                if (! $item->type->isScorable()) {
                    continue;
                }
                $raw = $responseByItem[$item->key]['numeric']
                    ?? $responseByItem[$item->key]['boolean']
                    ?? null;
                $norm = $item->normalizedScore($raw);
                if ($norm !== null) {
                    $scores[] = $norm;
                    $answered++;
                }
            }

            $sectionScore = $scores === [] ? null : array_sum($scores) / count($scores);
            $sections[$section->key] = [
                'score' => $sectionScore,
                'answered' => count($scores),
                'total' => count(array_filter($section->items, static fn ($i) => $i->type->isScorable())),
            ];

            if ($sectionScore !== null) {
                $weight = (float) (($config['section_weights'][$section->key] ?? 1));
                $weightedSum += $sectionScore * $weight;
                $weightUsed += $weight;
            }
        }

        $total = $weightUsed > 0 ? round($weightedSum / $weightUsed, 4) : null;

        return [
            'method' => (string) ($config['method'] ?? 'weighted_mean_normalized'),
            'sections' => $sections,
            'total' => $total,
            'band' => $total === null ? null : $this->band($total, $config['bands'] ?? []),
            'answered' => $answered,
            'required_total' => $requiredTotal,
        ];
    }

    /**
     * @param  list<array{min: float|int, label: string}>  $bands
     */
    private function band(float $total, array $bands): ?string
    {
        $sorted = $bands;
        usort($sorted, static fn ($a, $b): int => ($b['min'] <=> $a['min']));

        foreach ($sorted as $band) {
            if ($total >= (float) $band['min']) {
                return (string) $band['label'];
            }
        }

        return null;
    }
}
