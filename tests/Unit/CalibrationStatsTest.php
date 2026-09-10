<?php

declare(strict_types=1);

use App\Domain\Accountability\CalibrationStats;

it('reports perfect agreement when every rater gives identical scores', function () {
    $stats = CalibrationStats::compute([
        'a' => ['i1' => 3.0, 'i2' => 4.0],
        'b' => ['i1' => 3.0, 'i2' => 4.0],
        'c' => ['i1' => 3.0, 'i2' => 4.0],
    ]);

    expect($stats['n_rater'])->toBe(3)
        ->and($stats['n_item'])->toBe(2)
        ->and($stats['persen_kesepakatan'])->toBe(1.0)
        ->and($stats['deviasi_absolut_rata'])->toBe(0.0)
        ->and($stats['variansi_skor_total'])->toBe(0.0)
        ->and($stats['per_item']['i1']['variance'])->toBe(0.0);
});

it('is deterministic and quantifies disagreement', function () {
    $matrix = [
        'a' => ['i1' => 2.0, 'i2' => 3.0, 'i3' => 4.0],
        'b' => ['i1' => 3.0, 'i2' => 3.0, 'i3' => 2.0],
        'c' => ['i1' => 2.0, 'i2' => 4.0, 'i3' => 2.0],
    ];

    $first = CalibrationStats::compute($matrix);
    $second = CalibrationStats::compute($matrix);

    expect($first)->toBe($second)
        ->and($first['per_item']['i1']['kesepakatan'])->toBe(round(2 / 3, 3)) // 2 dari 3 rater sepakat pada 2.0
        ->and($first['per_item']['i2']['range'])->toBe(1.0)
        ->and($first['persen_kesepakatan'])->toBeGreaterThan(0.0)
        ->and($first['persen_kesepakatan'])->toBeLessThan(1.0)
        ->and($first['skor_total_per_rater']['a'])->toBe(9.0);
});

it('returns null kappa when there is only one rater', function () {
    $stats = CalibrationStats::compute(['solo' => ['i1' => 3.0]]);

    expect($stats['fleiss_kappa'])->toBeNull()
        ->and($stats['persen_kesepakatan'])->toBeNull();
});

it('computes a Fleiss kappa between -1 and 1 for mixed agreement', function () {
    $stats = CalibrationStats::compute([
        'a' => ['i1' => 1.0, 'i2' => 2.0, 'i3' => 3.0, 'i4' => 4.0],
        'b' => ['i1' => 1.0, 'i2' => 2.0, 'i3' => 4.0, 'i4' => 3.0],
        'c' => ['i1' => 2.0, 'i2' => 2.0, 'i3' => 3.0, 'i4' => 4.0],
    ]);

    expect($stats['fleiss_kappa'])->toBeGreaterThanOrEqual(-1.0)
        ->and($stats['fleiss_kappa'])->toBeLessThanOrEqual(1.0);
});
