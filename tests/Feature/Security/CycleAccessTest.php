<?php

declare(strict_types=1);

use App\Domain\Administration\PolicySettings;
use App\Models\Dinas;
use App\Models\Observation;
use App\Models\Sekolah;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;

it('does not let one supervisor open another supervisor cycle', function () {
    $cycle = SupervisionCycle::factory()->create();
    $intruder = User::factory()->supervisor()->create();

    $this->actingAs($intruder)->get(route('cycles.show', $cycle))->assertForbidden();
});

it('lets the assigned guru view their own cycle but not observe it', function () {
    $cycle = SupervisionCycle::factory()->status(CycleStatus::Scheduled)->create()->load('guru');
    $guru = $cycle->guru;

    $this->actingAs($guru)->get(route('cycles.show', $cycle))->assertOk();
    $this->actingAs($guru)->get(route('cycles.observe', $cycle))->assertForbidden();
});

it('hides observation drafts from the guru until finalized', function () {
    $cycle = SupervisionCycle::factory()->status(CycleStatus::Scheduled)->create()->load(['guru', 'supervisor']);
    $observation = Observation::factory()->for($cycle, 'cycle')->create([
        'observer_id' => $cycle->supervisor_id,
        'status' => 'draft',
    ]);

    expect($cycle->guru->can('view', $observation))->toBeFalse();

    $observation->update(['status' => 'final']);
    expect($cycle->guru->fresh()->can('view', $observation))->toBeTrue();
});

it('gates admin dinas cycle-detail access behind a policy setting', function () {
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();
    $supervisor = User::factory()->atSekolah($sekolah)->supervisor(SupervisorType::KepalaSekolah)->create();
    $guru = User::factory()->atSekolah($sekolah)->guru()->create();
    $cycle = SupervisionCycle::factory()->forPair($supervisor, $guru)->create();

    $adminDinas = User::factory()->create();
    $adminDinas->assignRole(Role::AdminDinas, $dinas);

    // default: dinas.can_view_cycle_detail = false
    expect($adminDinas->can('view', $cycle))->toBeFalse();

    app(PolicySettings::class)->set('dinas.can_view_cycle_detail', true, $dinas->id);
    expect($adminDinas->fresh()->can('view', $cycle->fresh()))->toBeTrue();
});
