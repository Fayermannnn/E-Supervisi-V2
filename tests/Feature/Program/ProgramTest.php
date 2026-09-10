<?php

declare(strict_types=1);

use App\Domain\Program\Actions\GenerateProgramCycles;
use App\Domain\Program\Actions\SaveAnnualProgram;
use App\Domain\Program\Actions\SyncProgramTargets;
use App\Models\AnnualProgram;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;

it('lets a supervisor create a program and generate DRAFT cycles for binaan', function () {
    $c = fase4Pair();
    $guru2 = User::factory()->atSekolah($c['sekolah'])->guru()->create();
    App\Models\SupervisorAssignment::create([
        'supervisor_id' => $c['supervisor']->id, 'guru_id' => $guru2->id, 'mulai' => now()->subMonth()->toDateString(),
    ]);

    $program = app(SaveAnnualProgram::class)->handle($c['supervisor'], null, [
        'judul' => 'Program Semester Ganjil', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'catatan' => null,
    ]);

    app(SyncProgramTargets::class)->handle($c['supervisor'], $program, [
        ['guru_id' => $c['guru']->id, 'fokus_ringkas' => 'Aktivasi', 'rencana_mulai' => null, 'rencana_selesai' => null],
        ['guru_id' => $guru2->id, 'fokus_ringkas' => null, 'rencana_mulai' => null, 'rencana_selesai' => null],
    ]);

    $result = app(GenerateProgramCycles::class)->handle($c['supervisor'], $program->refresh());

    expect($result['dibuat'])->toBe(2)
        ->and(SupervisionCycle::where('program_id', $program->id)->count())->toBe(2)
        ->and(SupervisionCycle::where('program_id', $program->id)->pluck('status')->unique()->all())->toBe([CycleStatus::Draft])
        ->and($program->refresh()->status)->toBe(AnnualProgram::STATUS_AKTIF);
});

it('is idempotent — a second generate creates nothing new', function () {
    $c = fase4Pair();
    $program = app(SaveAnnualProgram::class)->handle($c['supervisor'], null, [
        'judul' => 'Program X', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'catatan' => null,
    ]);
    app(SyncProgramTargets::class)->handle($c['supervisor'], $program, [
        ['guru_id' => $c['guru']->id, 'fokus_ringkas' => null, 'rencana_mulai' => null, 'rencana_selesai' => null],
    ]);
    app(GenerateProgramCycles::class)->handle($c['supervisor'], $program->refresh());

    expect(fn () => app(GenerateProgramCycles::class)->handle($c['supervisor'], $program->refresh()))
        ->toThrow(DomainException::class);
    expect(SupervisionCycle::where('program_id', $program->id)->count())->toBe(1);
});

it('rejects targeting a teacher who is not an active binaan', function () {
    $c = fase4Pair();
    $stranger = User::factory()->guru()->create();
    $program = app(SaveAnnualProgram::class)->handle($c['supervisor'], null, [
        'judul' => 'Program Y', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'catatan' => null,
    ]);

    expect(fn () => app(SyncProgramTargets::class)->handle($c['supervisor'], $program, [
        ['guru_id' => $stranger->id, 'fokus_ringkas' => null, 'rencana_mulai' => null, 'rencana_selesai' => null],
    ]))->toThrow(DomainException::class);
});

it('forbids another supervisor from editing a program they do not own', function () {
    $c = fase4Pair();
    $other = User::factory()->supervisor()->create();
    $program = app(SaveAnnualProgram::class)->handle($c['supervisor'], null, [
        'judul' => 'Program Z', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'catatan' => null,
    ]);

    expect(fn () => app(SaveAnnualProgram::class)->handle($other, $program, [
        'judul' => 'Diretas', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'catatan' => null,
    ]))->toThrow(Illuminate\Auth\Access\AuthorizationException::class);
});

it('denies program management to a guru via the gate', function () {
    $this->actingAs(fase4Pair()['guru']);
    expect(auth()->user()->can(App\Support\Enums\Permission::ManageAnnualProgram->value))->toBeFalse();
});
