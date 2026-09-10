<?php

declare(strict_types=1);

use App\Domain\Evaluation\ExpertJudgmentInstrument;
use App\Domain\Evaluation\ExpertJudgmentStats;
use App\Domain\Evaluation\UsabilityQuestionnaire;

/**
 * @param  1|2|3|4|5  $kualitas
 */
function uniformReview(string $relevansi, int $kualitas, int $susOdd = 5, int $susEven = 1): array
{
    $jawaban = ['relevansi' => [], 'kualitas' => [], 'sus' => []];
    foreach (array_keys(ExpertJudgmentInstrument::aspects()) as $aspek) {
        $jawaban['relevansi'][$aspek] = $relevansi;
        $jawaban['kualitas'][$aspek] = $kualitas;
    }
    foreach (UsabilityQuestionnaire::items() as $key => $meta) {
        $jawaban['sus'][$key] = $meta['positif'] ? $susOdd : $susEven;
    }

    return ExpertJudgmentInstrument::normalize($jawaban);
}

it('computes CVR, CVI, Aiken V, and SUS for unanimous experts', function () {
    $reviews = array_fill(0, 8, uniformReview(ExpertJudgmentInstrument::RELEVANSI_ESENSIAL, 5));
    $rumpun = array_fill(0, 8, 'sistem_informasi');

    $stats = ExpertJudgmentStats::compute($reviews, $rumpun);
    $firstAspek = array_key_first(ExpertJudgmentInstrument::aspects());

    expect($stats['n_ahli'])->toBe(8)
        ->and($stats['nilai_kritis_cvr'])->toBe(0.85)
        ->and($stats['cvr'][$firstAspek]['cvr'])->toBe(1.0)
        ->and($stats['cvr'][$firstAspek]['signifikan'])->toBeTrue()
        ->and($stats['cvi'])->toBe(1.0)
        ->and($stats['aiken'][$firstAspek]['v'])->toBe(1.0)
        ->and($stats['aiken_v_rata'])->toBe(1.0)
        ->and($stats['sus']['rata'])->toBe(100.0)
        ->and($stats['sus']['interpretasi'])->toBe('Sangat baik (A)');
});

it('is deterministic and reflects partial agreement', function () {
    // 5 dari 8 menilai "esensial", sisanya "berguna"; kualitas 4.
    $reviews = array_merge(
        array_fill(0, 5, uniformReview(ExpertJudgmentInstrument::RELEVANSI_ESENSIAL, 4)),
        array_fill(0, 3, uniformReview(ExpertJudgmentInstrument::RELEVANSI_BERGUNA, 4)),
    );
    $rumpun = array_fill(0, 8, 'manajemen_pendidikan');

    $a = ExpertJudgmentStats::compute($reviews, $rumpun);
    $b = ExpertJudgmentStats::compute($reviews, $rumpun);
    $aspek = array_key_first(ExpertJudgmentInstrument::aspects());

    expect($a)->toBe($b)
        ->and($a['cvr'][$aspek]['cvr'])->toBe(0.25)          // (5 - 4) / 4
        ->and($a['cvr'][$aspek]['signifikan'])->toBeFalse()  // 0.25 < 0.85
        ->and($a['aiken'][$aspek]['v'])->toBe(0.75)          // (3*8) / (8*4)
        ->and($a['per_rumpun']['manajemen_pendidikan']['n'])->toBe(8);
});

it('returns a null CVR critical value below five panelists', function () {
    $reviews = array_fill(0, 3, uniformReview(ExpertJudgmentInstrument::RELEVANSI_ESENSIAL, 5));
    $stats = ExpertJudgmentStats::compute($reviews, ['sistem_informasi', 'sistem_informasi', 'manajemen_pendidikan']);

    expect($stats['nilai_kritis_cvr'])->toBeNull()
        ->and($stats['cvr'][array_key_first(ExpertJudgmentInstrument::aspects())]['signifikan'])->toBeFalse()
        ->and($stats['per_rumpun'])->toHaveKeys(['sistem_informasi', 'manajemen_pendidikan']);
});

it('scores the SUS scale per the standard rubric', function () {
    $allFive = [];
    foreach (array_keys(UsabilityQuestionnaire::items()) as $k) {
        $allFive[$k] = 5;
    }

    // Semua 5: butir positif = 4, butir negatif = 0 → (5*4)*2.5 = 50
    expect(UsabilityQuestionnaire::score($allFive))->toBe(50.0)
        ->and(UsabilityQuestionnaire::interpret(50.0))->toContain('F');
});
