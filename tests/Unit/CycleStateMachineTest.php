<?php

declare(strict_types=1);

use App\Domain\Supervision\Exceptions\InvalidTransitionException;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\Instrument;
use App\Models\PlanningAgreement;
use App\Models\SupervisionCycle;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * @return array{0: SupervisionCycle, 1: App\Models\User}
 */
function draftCycleWithSupervisor(CycleStatus $status = CycleStatus::Draft): array
{
    $cycle = SupervisionCycle::factory()->create(['status' => $status])->load(['supervisor', 'guru']);

    return [$cycle, $cycle->supervisor];
}

it('rejects a transition that is not on the map', function () {
    [$cycle, $supervisor] = draftCycleWithSupervisor();

    expect(fn () => app(CycleStateMachine::class)->transition($cycle, CycleStatus::Reported, $supervisor))
        ->toThrow(InvalidTransitionException::class);
});

it('is a no-op when transitioning to the same status', function () {
    [$cycle, $supervisor] = draftCycleWithSupervisor();

    $result = app(CycleStateMachine::class)->transition($cycle, CycleStatus::Draft, $supervisor);

    expect($result->status)->toBe(CycleStatus::Draft);
    $this->assertDatabaseHas('audit_logs', ['action' => 'cycle.transition_noop']);
});

it('blocks Draft to Scheduled without a fully agreed planning agreement', function () {
    [$cycle, $supervisor] = draftCycleWithSupervisor();

    expect(fn () => app(CycleStateMachine::class)->transition($cycle, CycleStatus::Scheduled, $supervisor))
        ->toThrow(InvalidTransitionException::class);
});

it('allows Draft to Scheduled once both parties agree and records a transition row', function () {
    [$cycle, $supervisor] = draftCycleWithSupervisor();
    $version = Instrument::factory()->published()->create()->versions()->first();

    PlanningAgreement::create([
        'cycle_id' => $cycle->id,
        'fokus_observasi' => 'Fokus',
        'instrument_id' => $version->instrument_id,
        'instrument_version_id' => $version->id,
        'tipe_observasi' => 'sinkron',
        'jadwal_mulai' => now()->addDay(),
        'disepakati_guru_at' => now(),
        'disepakati_supervisor_at' => now(),
    ]);

    app(CycleStateMachine::class)->transition($cycle->refresh(), CycleStatus::Scheduled, $supervisor);

    expect($cycle->refresh()->status)->toBe(CycleStatus::Scheduled);
    $this->assertDatabaseHas('cycle_status_transitions', [
        'cycle_id' => $cycle->id, 'from_status' => 0, 'to_status' => 1,
    ]);
});

it('requires a reason to cancel', function () {
    [$cycle, $supervisor] = draftCycleWithSupervisor();

    expect(fn () => app(CycleStateMachine::class)->transition($cycle, CycleStatus::Canceled, $supervisor, null))
        ->toThrow(InvalidTransitionException::class);

    app(CycleStateMachine::class)->transition($cycle, CycleStatus::Canceled, $supervisor, 'Guru pindah tugas');
    expect($cycle->refresh()->status)->toBe(CycleStatus::Canceled)
        ->and($cycle->canceled_reason)->toBe('Guru pindah tugas');
});

it('refuses a system-only transition when triggered by a person', function () {
    [$cycle] = draftCycleWithSupervisor(CycleStatus::FollowUpActive);
    $guru = $cycle->guru;
    $guru->assignRole(Role::Guru);

    expect(fn () => app(CycleStateMachine::class)->transition($cycle, CycleStatus::FollowUpOverdue, $guru))
        ->toThrow(InvalidTransitionException::class);
});

it('blocks phase 3 transitions until those modules land', function () {
    [$cycle, $supervisor] = draftCycleWithSupervisor(CycleStatus::ObservationDone);

    expect(fn () => app(CycleStateMachine::class)->transition($cycle, CycleStatus::AnalysisDone, $supervisor))
        ->toThrow(InvalidTransitionException::class, 'Fase 3');
});

it('never lets a null (AI/agent) actor move a human-gated transition', function () {
    [$cycle] = draftCycleWithSupervisor();

    expect(fn () => app(CycleStateMachine::class)->transition($cycle, CycleStatus::Scheduled, null))
        ->toThrow(InvalidTransitionException::class);
});
