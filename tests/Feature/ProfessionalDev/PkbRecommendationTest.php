<?php

declare(strict_types=1);

use App\Domain\ProfessionalDev\Actions\GeneratePkbRecommendations;
use App\Domain\ProfessionalDev\Actions\RespondPkbRecommendation;
use App\Models\AnalysisFinding;
use App\Models\AnalysisResult;
use App\Models\PkbCatalogItem;
use App\Models\PkbRecommendation;
use App\Support\Enums\CycleStatus;

function catalogItem(array $tags, ?string $dinasId = null): PkbCatalogItem
{
    return PkbCatalogItem::create([
        'judul' => 'Pelatihan '.implode(' ', $tags),
        'deskripsi' => 'Materi PKB untuk '.implode(', ', $tags),
        'tipe' => 'pelatihan',
        'tags' => $tags,
        'kompetensi' => [],
        'pemilik_dinas_id' => $dinasId,
        'status' => PkbCatalogItem::STATUS_TERBIT,
        'created_by' => App\Models\User::factory()->adminSistem()->create()->id,
    ]);
}

it('derives PKB recommendations from finalized analysis development areas', function () {
    $c = fase4Cycle(CycleStatus::AnalysisDone);
    $result = AnalysisResult::where('cycle_id', $c['cycle']->id)->sole();
    $result->findings()->create([
        'kategori' => AnalysisFinding::KATEGORI_PENGEMBANGAN,
        'deskripsi' => 'Menguatkan pemberian pertanyaan pemantik dan diskusi kelompok pada kegiatan inti.',
        'bukti_ref' => null, 'prioritas' => 2, 'urutan' => 9,
    ]);

    catalogItem(['pertanyaan pemantik', 'diskusi kelompok']);
    catalogItem(['administrasi sekolah']); // tidak relevan

    $out = app(GeneratePkbRecommendations::class)->handle($c['supervisor'], $c['cycle']->refresh());

    expect($out['dibuat'])->toBe(1)
        ->and(PkbRecommendation::where('cycle_id', $c['cycle']->id)->count())->toBe(1)
        ->and(PkbRecommendation::where('cycle_id', $c['cycle']->id)->sole()->sumber)->toBe(PkbRecommendation::SUMBER_ANALISIS);
});

it('marks a recommendation as rtl_berulang when the area recurs across the teacher cycles', function () {
    $c = fase4Cycle(CycleStatus::AnalysisDone);

    // Siklus lampau untuk guru yang sama dengan area serupa.
    $past = fase4Cycle(CycleStatus::AnalysisDone);
    // paksa guru & dinas sama
    $past['cycle']->forceFill(['guru_id' => $c['guru']->id, 'dinas_id' => $c['cycle']->dinas_id])->save();

    foreach ([$c, $past] as $ctx) {
        AnalysisResult::where('cycle_id', $ctx['cycle']->id)->sole()->findings()->create([
            'kategori' => AnalysisFinding::KATEGORI_PENGEMBANGAN,
            'deskripsi' => 'Menguatkan pemberian pertanyaan pemantik.',
            'bukti_ref' => null, 'prioritas' => 2, 'urutan' => 8,
        ]);
    }

    catalogItem(['pertanyaan pemantik']);

    app(GeneratePkbRecommendations::class)->handle($c['supervisor'], $c['cycle']->refresh());

    expect(PkbRecommendation::where('cycle_id', $c['cycle']->id)->sole()->sumber)
        ->toBe(PkbRecommendation::SUMBER_RTL_BERULANG);
});

it('lets the teacher accept a recommendation but not the supervisor of another cycle', function () {
    $c = fase4Cycle(CycleStatus::AnalysisDone);
    AnalysisResult::where('cycle_id', $c['cycle']->id)->sole()->findings()->create([
        'kategori' => AnalysisFinding::KATEGORI_PENGEMBANGAN,
        'deskripsi' => 'Menguatkan pertanyaan pemantik.', 'bukti_ref' => null, 'prioritas' => 2, 'urutan' => 7,
    ]);
    catalogItem(['pertanyaan pemantik']);
    app(GeneratePkbRecommendations::class)->handle($c['supervisor'], $c['cycle']->refresh());
    $rec = PkbRecommendation::where('cycle_id', $c['cycle']->id)->sole();

    $stranger = App\Models\User::factory()->supervisor()->create();
    expect(fn () => app(RespondPkbRecommendation::class)->handle($stranger, $rec, 'dipilih'))
        ->toThrow(Illuminate\Auth\Access\AuthorizationException::class);

    app(RespondPkbRecommendation::class)->handle($c['guru'], $rec, 'dipilih');
    expect($rec->refresh()->status)->toBe(PkbRecommendation::STATUS_DIPILIH);
});

it('refuses to generate recommendations before analysis is final', function () {
    $c = fase4Cycle(CycleStatus::ObservationDone);

    expect(fn () => app(GeneratePkbRecommendations::class)->handle($c['supervisor'], $c['cycle']))
        ->toThrow(DomainException::class);
});
