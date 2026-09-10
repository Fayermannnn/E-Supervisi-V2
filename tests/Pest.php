<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case binding
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Domain-specific test helpers are added here as modules land
| (e.g. actingAsRole() in Phase 1 A3 once RBAC exists).
|
*/

/**
 * Pasangan supervisor–guru binaan aktif pada satu sekolah/dinas.
 *
 * @return array{dinas: App\Models\Dinas, sekolah: App\Models\Sekolah, supervisor: App\Models\User, guru: App\Models\User, version: App\Models\InstrumentVersion}
 */
function fase4Pair(): array
{
    $dinas = App\Models\Dinas::factory()->create();
    $sekolah = App\Models\Sekolah::factory()->forDinas($dinas)->create();
    $supervisor = App\Models\User::factory()->atSekolah($sekolah)->supervisor(App\Support\Enums\SupervisorType::KepalaSekolah)->create();
    $guru = App\Models\User::factory()->atSekolah($sekolah)->guru()->create();
    App\Models\SupervisorAssignment::create([
        'supervisor_id' => $supervisor->id,
        'guru_id' => $guru->id,
        'mulai' => now()->subMonth()->toDateString(),
    ]);
    $version = App\Models\Instrument::factory()->published()->create()->versions()->first();

    return compact('dinas', 'sekolah', 'supervisor', 'guru', 'version');
}

/**
 * Bangun siklus lengkap sampai status tertentu (default: REPORTED) memakai
 * Action domain nyata. Mengembalikan konteks fase4Pair() + `cycle`.
 *
 * @return array{dinas: App\Models\Dinas, sekolah: App\Models\Sekolah, supervisor: App\Models\User, guru: App\Models\User, version: App\Models\InstrumentVersion, cycle: App\Models\SupervisionCycle}
 */
function fase4Cycle(App\Support\Enums\CycleStatus $until = App\Support\Enums\CycleStatus::Reported): array
{
    $c = fase4Pair();
    $s = $c['supervisor'];
    $g = $c['guru'];
    $version = $c['version'];

    $cycle = app(App\Domain\Supervision\Actions\CreateCycle::class)->handle($s, $g, '2026/2027', 'ganjil', 'Uji Fase 4');
    if ($until === App\Support\Enums\CycleStatus::Draft) {
        return [...$c, 'cycle' => $cycle];
    }

    app(App\Domain\Planning\Actions\SavePlanningAgreement::class)->handle($s, $cycle, [
        'fokus_observasi' => 'Partisipasi aktif peserta didik',
        'instrument_version_id' => $version->id,
        'tipe_observasi' => 'sinkron',
        'jadwal_mulai' => now()->addDay()->toDateTimeString(),
    ]);
    app(App\Domain\Planning\Actions\RecordPlanningAgreementConsent::class)->handle($g, $cycle);
    app(App\Domain\Planning\Actions\RecordPlanningAgreementConsent::class)->handle($s, $cycle);
    if ($until === App\Support\Enums\CycleStatus::Scheduled) {
        return [...$c, 'cycle' => $cycle->refresh()];
    }

    $obs = app(App\Domain\Observation\Actions\StartObservation::class)->handle($s, $cycle->refresh());
    $rows = [];
    foreach ($version->schema()->requiredItemKeys() as $key) {
        $rows[] = ['item_key' => $key, 'section_key' => 'inti', 'value' => 3];
    }
    app(App\Domain\Observation\Actions\SaveObservation::class)->handle($obs, $rows);
    app(App\Domain\Observation\Actions\FinalizeObservation::class)->handle($s, $obs->refresh());
    if ($until === App\Support\Enums\CycleStatus::ObservationDone) {
        return [...$c, 'cycle' => $cycle->refresh()];
    }

    $analysis = app(App\Domain\Analysis\Actions\PerformAnalysis::class)->handle($s, $cycle->refresh());
    app(App\Domain\Analysis\Actions\SaveAnalysisSummary::class)->handle($s, $analysis, str_repeat('Ringkasan analisis memadai. ', 4));
    app(App\Domain\Analysis\Actions\FinalizeAnalysis::class)->handle($s, $analysis->refresh());
    if ($until === App\Support\Enums\CycleStatus::AnalysisDone) {
        return [...$c, 'cycle' => $cycle->refresh()];
    }

    $session = app(App\Domain\Feedback\Actions\StartFeedbackSession::class)->handle($cycle->refresh());
    app(App\Domain\Feedback\Actions\PostFeedbackMessage::class)->handle($s, $session, 'kesepakatan', 'Sepakat.');
    app(App\Domain\Feedback\Actions\AcknowledgeFeedback::class)->handle($g, $session);
    if ($until === App\Support\Enums\CycleStatus::FeedbackGiven) {
        return [...$c, 'cycle' => $cycle->refresh()];
    }

    $plan = app(App\Domain\FollowUp\Actions\CreateFollowUpPlan::class)->handle(
        $s, $cycle->refresh(), 'Tingkatkan partisipasi.', now()->addWeek()->toDateString(),
        [['deskripsi' => 'Butir A', 'indikator_keberhasilan' => 'Indikator A']],
    );
    app(App\Domain\FollowUp\Actions\UpdateFollowUpItem::class)->handle($s, $plan->items()->first(), 'selesai');
    if ($until === App\Support\Enums\CycleStatus::FollowUpActive) {
        return [...$c, 'cycle' => $cycle->refresh()];
    }

    app(App\Domain\Reporting\Actions\CompileCycleReport::class)->handle($s, $cycle->refresh());

    return [...$c, 'cycle' => $cycle->refresh()];
}
