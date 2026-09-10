<?php

declare(strict_types=1);

use App\Domain\Analysis\SchemaDrivenScorer;
use App\Models\Instrument;
use App\Models\Observation;
use App\Models\SupervisionCycle;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function observationWith(array $values): Observation
{
    $version = Instrument::factory()->published()->create()->versions()->first();
    $cycle = SupervisionCycle::factory()->create();
    $obs = Observation::factory()->for($cycle, 'cycle')->create([
        'observer_id' => $cycle->supervisor_id,
        'instrument_version_id' => $version->id,
    ]);

    foreach ($values as $key => $v) {
        $obs->responses()->create([
            'instrument_version_id' => $version->id,
            'section_key' => 'inti',
            'item_key' => $key,
            'value_numeric' => $v,
        ]);
    }

    return $obs;
}

it('returns a perfect score when every likert item is maxed', function () {
    $version = Instrument::factory()->published()->create()->versions()->first();
    $keys = $version->schema()->requiredItemKeys();
    $obs = observationWith(array_fill_keys($keys, 4));

    $summary = app(SchemaDrivenScorer::class)->score($obs);

    expect($summary['total'])->toBe(1.0)
        ->and($summary['band'])->toBe('Sangat Baik');
});

it('returns the lowest band when every item is minimum', function () {
    $version = Instrument::factory()->published()->create()->versions()->first();
    $keys = $version->schema()->requiredItemKeys();
    $obs = observationWith(array_fill_keys($keys, 1));

    $summary = app(SchemaDrivenScorer::class)->score($obs);

    expect($summary['total'])->toBe(0.0)
        ->and($summary['band'])->toBe('Perlu Pembinaan');
});

it('weights the inti section twice as heavily as pendahuluan', function () {
    // Only answer one item in each of two sections with contrasting scores.
    $obs = observationWith(['apersepsi' => 4, 'penguasaan_materi' => 1]);

    $summary = app(SchemaDrivenScorer::class)->score($obs);

    // pendahuluan=1.0 (weight 1), inti=0.0 (weight 2) -> (1*1 + 0*2) / 3 = 0.333
    expect($summary['total'])->toBe(0.3333);
});
