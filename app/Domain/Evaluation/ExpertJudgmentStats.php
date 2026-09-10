<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

/**
 * Menghitung indeks validitas isi (CVR/CVI Lawshe 1975), Aiken's V (1985), dan
 * skor SUS dari kumpulan jawaban ahli. Deterministik & bebas efek samping —
 * diuji unit (ExpertJudgmentStatsTest). Fase 5 / DSR Artikel 3.
 */
final class ExpertJudgmentStats
{
    /**
     * Nilai kritis CVR minimum (Lawshe, satu sisi, p = .05) per jumlah panelis.
     *
     * @var array<int, float>
     */
    private const CVR_CRITICAL = [
        5 => 0.99, 6 => 0.99, 7 => 0.99, 8 => 0.85, 9 => 0.78, 10 => 0.62,
        11 => 0.59, 12 => 0.56, 13 => 0.54, 14 => 0.51, 15 => 0.49, 20 => 0.42,
        25 => 0.37, 30 => 0.33, 35 => 0.31, 40 => 0.29,
    ];

    /**
     * @param  list<array<string, mixed>>  $reviews  jawaban ter-normalisasi per ahli
     * @param  list<string>  $rumpunList  rumpun tiap ahli (indeks selaras $reviews)
     * @return array{
     *     n_ahli: int,
     *     cvr: array<string, array{n_esensial: int, cvr: float, signifikan: bool}>,
     *     cvi: float|null,
     *     nilai_kritis_cvr: float|null,
     *     aiken: array<string, array{mean: float, v: float}>,
     *     aiken_v_rata: float|null,
     *     sus: array{per_ahli: list<float>, rata: float|null, interpretasi: string|null},
     *     per_rumpun: array<string, array{n: int, aiken_v_rata: float|null, sus_rata: float|null}>
     * }
     */
    public static function compute(array $reviews, array $rumpunList): array
    {
        $n = count($reviews);
        $aspects = array_keys(ExpertJudgmentInstrument::aspects());

        $cvr = [];
        $cvrValues = [];
        $critical = self::criticalCvr($n);

        foreach ($aspects as $aspek) {
            $essential = 0;
            foreach ($reviews as $r) {
                if (($r['relevansi'][$aspek] ?? null) === ExpertJudgmentInstrument::RELEVANSI_ESENSIAL) {
                    $essential++;
                }
            }
            $value = $n > 0 ? round(($essential - $n / 2) / ($n / 2), 3) : 0.0;
            $cvr[$aspek] = [
                'n_esensial' => $essential,
                'cvr' => $value,
                'signifikan' => $critical !== null && $value >= $critical,
            ];
            $cvrValues[] = $value;
        }

        $aiken = [];
        $vValues = [];
        $c = ExpertJudgmentInstrument::KUALITAS_MAX;
        foreach ($aspects as $aspek) {
            $ratings = [];
            foreach ($reviews as $r) {
                $ratings[] = (int) ($r['kualitas'][$aspek] ?? ExpertJudgmentInstrument::KUALITAS_MIN);
            }
            $s = array_sum(array_map(static fn (int $x): int => $x - ExpertJudgmentInstrument::KUALITAS_MIN, $ratings));
            $v = $n > 0 ? round($s / ($n * ($c - 1)), 3) : 0.0;
            $mean = $ratings === [] ? 0.0 : round(array_sum($ratings) / count($ratings), 3);
            $aiken[$aspek] = ['mean' => $mean, 'v' => $v];
            $vValues[] = $v;
        }

        $susPer = [];
        foreach ($reviews as $r) {
            /** @var array<string, int> $susAnswers */
            $susAnswers = is_array($r['sus'] ?? null) ? $r['sus'] : [];
            $susPer[] = UsabilityQuestionnaire::score($susAnswers);
        }
        $susMean = $susPer === [] ? null : round(array_sum($susPer) / count($susPer), 1);

        return [
            'n_ahli' => $n,
            'cvr' => $cvr,
            'cvi' => $cvrValues === [] ? null : round(array_sum($cvrValues) / count($cvrValues), 3),
            'nilai_kritis_cvr' => $critical,
            'aiken' => $aiken,
            'aiken_v_rata' => $vValues === [] ? null : round(array_sum($vValues) / count($vValues), 3),
            'sus' => [
                'per_ahli' => $susPer,
                'rata' => $susMean,
                'interpretasi' => $susMean === null ? null : UsabilityQuestionnaire::interpret($susMean),
            ],
            'per_rumpun' => self::perRumpun($reviews, $rumpunList),
        ];
    }

    private static function criticalCvr(int $n): ?float
    {
        if ($n < 5) {
            return null;
        }
        if (isset(self::CVR_CRITICAL[$n])) {
            return self::CVR_CRITICAL[$n];
        }

        // Ambil nilai kritis panelis terdekat yang ≤ n (konservatif).
        $keys = array_keys(self::CVR_CRITICAL);
        rsort($keys);
        foreach ($keys as $k) {
            if ($k <= $n) {
                return self::CVR_CRITICAL[$k];
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $reviews
     * @param  list<string>  $rumpunList
     * @return array<string, array{n: int, aiken_v_rata: float|null, sus_rata: float|null}>
     */
    private static function perRumpun(array $reviews, array $rumpunList): array
    {
        $aspects = array_keys(ExpertJudgmentInstrument::aspects());
        $c = ExpertJudgmentInstrument::KUALITAS_MAX;

        /** @var array<string, list<int>> $byRumpun */
        $byRumpun = [];
        foreach ($reviews as $i => $review) {
            $rumpun = $rumpunList[$i] ?? 'lainnya';
            $byRumpun[$rumpun][] = $i;
        }

        $out = [];
        foreach ($byRumpun as $rumpun => $indexes) {
            $group = array_map(static fn (int $i): array => $reviews[$i], $indexes);
            $gn = count($group);

            $vSum = 0.0;
            foreach ($aspects as $aspek) {
                $s = 0;
                foreach ($group as $r) {
                    $s += (int) ($r['kualitas'][$aspek] ?? ExpertJudgmentInstrument::KUALITAS_MIN) - ExpertJudgmentInstrument::KUALITAS_MIN;
                }
                $vSum += $gn > 0 ? $s / ($gn * ($c - 1)) : 0.0;
            }

            $susScores = [];
            foreach ($group as $r) {
                $susScores[] = UsabilityQuestionnaire::score(is_array($r['sus'] ?? null) ? $r['sus'] : []);
            }

            $out[$rumpun] = [
                'n' => $gn,
                'aiken_v_rata' => $aspects === [] ? null : round($vSum / count($aspects), 3),
                'sus_rata' => $susScores === [] ? null : round(array_sum($susScores) / count($susScores), 1),
            ];
        }

        return $out;
    }
}
