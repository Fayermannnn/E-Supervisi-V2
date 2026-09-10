<?php

declare(strict_types=1);

namespace App\Domain\Accountability;

/**
 * Statistik reliabilitas antar-penilai untuk sesi kalibrasi (M12, @provisional).
 * Deterministik dan bebas efek samping — diuji unit (CalibrationStatsTest).
 *
 * Masukan: peta rater => (item_key => nilai). Semua rater diasumsikan menilai
 * himpunan item yang sama; item yang tidak lengkap diabaikan per-item.
 */
final class CalibrationStats
{
    /**
     * @param  array<string, array<string, float>>  $byRater
     * @return array{
     *     n_rater: int,
     *     n_item: int,
     *     per_item: array<string, array{mean: float, variance: float, range: float, kesepakatan: float}>,
     *     persen_kesepakatan: float|null,
     *     deviasi_absolut_rata: float|null,
     *     skor_total_per_rater: array<string, float>,
     *     variansi_skor_total: float|null,
     *     fleiss_kappa: float|null
     * }
     */
    public static function compute(array $byRater): array
    {
        $raters = array_keys($byRater);
        $nRater = count($raters);

        $itemKeys = [];
        foreach ($byRater as $scores) {
            foreach (array_keys($scores) as $key) {
                $itemKeys[$key] = true;
            }
        }
        $itemKeys = array_keys($itemKeys);
        sort($itemKeys);

        $perItem = [];
        $agreements = [];
        $absDeviations = [];

        foreach ($itemKeys as $key) {
            $values = [];
            foreach ($raters as $rater) {
                if (array_key_exists($key, $byRater[$rater])) {
                    $values[] = (float) $byRater[$rater][$key];
                }
            }

            if (count($values) < 2) {
                continue;
            }

            $mean = array_sum($values) / count($values);
            $variance = self::variance($values, $mean);
            $range = max($values) - min($values);

            // Kesepakatan eksak: proporsi penilai yang sama dengan modus item.
            $modeCount = self::modeCount($values);
            $agreement = $modeCount / count($values);

            foreach ($values as $v) {
                $absDeviations[] = abs($v - $mean);
            }

            $perItem[$key] = [
                'mean' => round($mean, 3),
                'variance' => round($variance, 3),
                'range' => round($range, 3),
                'kesepakatan' => round($agreement, 3),
            ];
            $agreements[] = $agreement;
        }

        $totals = [];
        foreach ($raters as $rater) {
            $totals[$rater] = round(array_sum($byRater[$rater]), 3);
        }

        return [
            'n_rater' => $nRater,
            'n_item' => count($perItem),
            'per_item' => $perItem,
            'persen_kesepakatan' => $agreements === [] ? null : round(array_sum($agreements) / count($agreements), 3),
            'deviasi_absolut_rata' => $absDeviations === [] ? null : round(array_sum($absDeviations) / count($absDeviations), 3),
            'skor_total_per_rater' => $totals,
            'variansi_skor_total' => count($totals) < 2 ? null : round(self::variance(array_values($totals), array_sum($totals) / count($totals)), 3),
            'fleiss_kappa' => self::fleissKappa($byRater, $itemKeys, $raters),
        ];
    }

    /**
     * @param  list<float>  $values
     */
    private static function variance(array $values, float $mean): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($values as $v) {
            $sum += ($v - $mean) ** 2;
        }

        return $sum / count($values);
    }

    /**
     * @param  list<float>  $values
     */
    private static function modeCount(array $values): int
    {
        $counts = [];
        foreach ($values as $v) {
            $bucket = (string) $v;
            $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
        }

        return $counts === [] ? 0 : max($counts);
    }

    /**
     * Fleiss' kappa memperlakukan nilai (dibulatkan ke bilangan bulat) sebagai
     * kategori. Mengembalikan null bila tak terdefinisi (mis. kesepakatan sempurna
     * pada satu kategori, atau data tidak memadai).
     *
     * @param  array<string, array<string, float>>  $byRater
     * @param  list<string>  $itemKeys
     * @param  list<string>  $raters
     */
    private static function fleissKappa(array $byRater, array $itemKeys, array $raters): ?float
    {
        $nRater = count($raters);
        if ($nRater < 2 || $itemKeys === []) {
            return null;
        }

        // Hanya item yang dinilai lengkap oleh semua rater.
        $rows = [];
        $categories = [];
        foreach ($itemKeys as $key) {
            $cats = [];
            $complete = true;
            foreach ($raters as $rater) {
                if (! array_key_exists($key, $byRater[$rater])) {
                    $complete = false;
                    break;
                }
                $cat = (int) round($byRater[$rater][$key]);
                $cats[] = $cat;
                $categories[$cat] = true;
            }
            if ($complete) {
                $rows[] = $cats;
            }
        }

        $nItem = count($rows);
        if ($nItem === 0) {
            return null;
        }

        $categoryList = array_keys($categories);
        sort($categoryList);

        // p_j: proporsi seluruh penilaian pada kategori j.
        $pj = [];
        foreach ($categoryList as $cat) {
            $pj[$cat] = 0;
        }
        foreach ($rows as $cats) {
            foreach ($cats as $cat) {
                $pj[$cat]++;
            }
        }
        $totalRatings = $nItem * $nRater;
        foreach ($pj as $cat => $count) {
            $pj[$cat] = $count / $totalRatings;
        }

        // P_i: kesepakatan per item.
        $pBar = 0.0;
        foreach ($rows as $cats) {
            $sum = 0;
            $countByCat = [];
            foreach ($cats as $cat) {
                $countByCat[$cat] = ($countByCat[$cat] ?? 0) + 1;
            }
            foreach ($countByCat as $c) {
                $sum += $c * ($c - 1);
            }
            $pBar += $sum / ($nRater * ($nRater - 1));
        }
        $pBar /= $nItem;

        $peBar = 0.0;
        foreach ($pj as $p) {
            $peBar += $p ** 2;
        }

        $denom = 1 - $peBar;
        if (abs($denom) < 1e-9) {
            return null;
        }

        return round(($pBar - $peBar) / $denom, 3);
    }
}
