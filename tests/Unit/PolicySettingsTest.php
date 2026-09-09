<?php

declare(strict_types=1);

use App\Domain\Administration\PolicySettings;
use App\Models\Dinas;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns the documented default when nothing is stored', function () {
    expect(app(PolicySettings::class)->get('planning.teacher_reflection_required'))->toBeTrue();
});

it('lets a dinas override the global default', function () {
    $settings = app(PolicySettings::class);
    $dinas = Dinas::factory()->create();

    $settings->set('planning.teacher_reflection_required', false, $dinas->id);

    expect($settings->get('planning.teacher_reflection_required', $dinas->id))->toBeFalse()
        ->and($settings->get('planning.teacher_reflection_required'))->toBeTrue();
});

it('rejects an unknown policy key', function () {
    expect(fn () => app(PolicySettings::class)->get('made.up.key'))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps only one global row per key', function () {
    $settings = app(PolicySettings::class);
    $settings->set('observation.max_media_mb', 100, null);
    $settings->set('observation.max_media_mb', 150, null);

    expect(App\Models\PolicySetting::where('key', 'observation.max_media_mb')->whereNull('dinas_id')->count())->toBe(1)
        ->and($settings->get('observation.max_media_mb'))->toBe(150);
});
