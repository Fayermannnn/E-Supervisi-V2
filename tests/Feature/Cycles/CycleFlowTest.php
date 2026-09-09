<?php

declare(strict_types=1);

use App\Domain\Observation\Actions\FinalizeObservation;
use App\Domain\Observation\Actions\SaveObservation;
use App\Domain\Observation\Actions\StartObservation;
use App\Domain\Planning\Actions\RecordPlanningAgreementConsent;
use App\Domain\Planning\Actions\SavePlanningAgreement;
use App\Domain\Supervision\Actions\CreateCycle;
use App\Models\Dinas;
use App\Models\Instrument;
use App\Models\Sekolah;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\SupervisorType;
use Illuminate\Support\Facades\Notification;

function supervisionScenario(): array
{
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();
    $supervisor = User::factory()->atSekolah($sekolah)->supervisor(SupervisorType::KepalaSekolah)->create();
    $guru = User::factory()->atSekolah($sekolah)->guru()->create();
    SupervisorAssignment::create([
        'supervisor_id' => $supervisor->id, 'guru_id' => $guru->id, 'mulai' => now()->subMonth()->toDateString(),
    ]);
    $version = Instrument::factory()->published()->create()->versions()->first();

    return compact('dinas', 'sekolah', 'supervisor', 'guru', 'version');
}

it('creates a cycle only for an active binaan', function () {
    $s = supervisionScenario();
    $stranger = User::factory()->guru()->create();

    expect(fn () => app(CreateCycle::class)->handle($s['supervisor'], $stranger, '2026/2027', 'ganjil', 'X'))
        ->toThrow(DomainException::class);

    $cycle = app(CreateCycle::class)->handle($s['supervisor'], $s['guru'], '2026/2027', 'ganjil', 'Uji');
    expect($cycle->status)->toBe(CycleStatus::Draft)
        ->and($cycle->dinas_id)->toBe($s['dinas']->id);
});

it('moves the cycle to Scheduled only after both parties consent and notifies the teacher', function () {
    Notification::fake();
    $s = supervisionScenario();
    $cycle = app(CreateCycle::class)->handle($s['supervisor'], $s['guru'], '2026/2027', 'ganjil', 'Uji');

    app(SavePlanningAgreement::class)->handle($s['supervisor'], $cycle, [
        'fokus_observasi' => 'Fokus', 'instrument_version_id' => $s['version']->id,
        'tipe_observasi' => 'sinkron', 'jadwal_mulai' => now()->addDay()->toDateTimeString(),
    ]);

    app(RecordPlanningAgreementConsent::class)->handle($s['guru'], $cycle);
    expect($cycle->refresh()->status)->toBe(CycleStatus::Draft);

    app(RecordPlanningAgreementConsent::class)->handle($s['supervisor'], $cycle);
    expect($cycle->refresh()->status)->toBe(CycleStatus::Scheduled);

    Notification::assertSentTo($s['guru'], App\Domain\Supervision\Notifications\CycleStatusNotification::class);
});

it('editing the focus after one consent resets both consents', function () {
    $s = supervisionScenario();
    $cycle = app(CreateCycle::class)->handle($s['supervisor'], $s['guru'], '2026/2027', 'ganjil', 'Uji');
    $save = app(SavePlanningAgreement::class);

    $save->handle($s['supervisor'], $cycle, [
        'fokus_observasi' => 'Fokus lama', 'instrument_version_id' => $s['version']->id,
        'tipe_observasi' => 'sinkron', 'jadwal_mulai' => now()->addDay()->toDateTimeString(),
    ]);
    app(RecordPlanningAgreementConsent::class)->handle($s['guru'], $cycle);

    $save->handle($s['supervisor'], $cycle, [
        'fokus_observasi' => 'Fokus baru', 'instrument_version_id' => $s['version']->id,
        'tipe_observasi' => 'sinkron', 'jadwal_mulai' => now()->addDay()->toDateTimeString(),
    ]);

    expect($cycle->planningAgreement()->first()->disepakati_guru_at)->toBeNull();
});

it('finalizes an observation only when every required item is answered, then advances the cycle', function () {
    $s = supervisionScenario();
    $cycle = app(CreateCycle::class)->handle($s['supervisor'], $s['guru'], '2026/2027', 'ganjil', 'Uji');
    app(SavePlanningAgreement::class)->handle($s['supervisor'], $cycle, [
        'fokus_observasi' => 'Fokus', 'instrument_version_id' => $s['version']->id,
        'tipe_observasi' => 'sinkron', 'jadwal_mulai' => now()->addDay()->toDateTimeString(),
    ]);
    app(RecordPlanningAgreementConsent::class)->handle($s['guru'], $cycle);
    app(RecordPlanningAgreementConsent::class)->handle($s['supervisor'], $cycle);

    $observation = app(StartObservation::class)->handle($s['supervisor'], $cycle->refresh());

    // Only one item answered -> finalize must fail
    app(SaveObservation::class)->handle($observation, [
        ['item_key' => 'apersepsi', 'section_key' => 'pendahuluan', 'value' => 3],
    ]);
    expect(fn () => app(FinalizeObservation::class)->handle($s['supervisor'], $observation->refresh()))
        ->toThrow(DomainException::class);

    // Answer all required items
    $rows = [];
    foreach ($s['version']->schema()->requiredItemKeys() as $key) {
        $rows[] = ['item_key' => $key, 'section_key' => 'inti', 'value' => 3];
    }
    app(SaveObservation::class)->handle($observation->refresh(), $rows);

    $result = app(FinalizeObservation::class)->handle($s['supervisor'], $observation->refresh());
    expect($result['observation']->status)->toBe(App\Domain\Observation\Enums\ObservationStatus::Final)
        ->and($cycle->refresh()->status)->toBe(CycleStatus::ObservationDone);
});
