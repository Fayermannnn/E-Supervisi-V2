<?php

declare(strict_types=1);

use App\Domain\Organization\Actions\AssignSupervisor;
use App\Models\Dinas;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\SupervisorType;

beforeEach(function () {
    $this->dinas = Dinas::factory()->create();
    $this->sekolah = Sekolah::factory()->forDinas($this->dinas)->create();
    $this->kepsek = User::factory()->atSekolah($this->sekolah)->supervisor(SupervisorType::KepalaSekolah)->create();
    $this->guru = User::factory()->atSekolah($this->sekolah)->guru()->create();
    $this->actor = User::factory()->adminSistem()->create();
});

it('creates a supervisor assignment', function () {
    $assignment = app(AssignSupervisor::class)->handle($this->actor, $this->kepsek, $this->guru, now()->toDateString());

    expect($assignment->supervisor_id)->toBe($this->kepsek->id)
        ->and($assignment->guru_id)->toBe($this->guru->id);
    $this->assertDatabaseHas('audit_logs', ['action' => 'supervisor_assignment.created']);
});

it('rejects an overlapping assignment for the same pair', function () {
    $action = app(AssignSupervisor::class);
    $action->handle($this->actor, $this->kepsek, $this->guru, now()->toDateString());

    expect(fn () => $action->handle($this->actor, $this->kepsek, $this->guru, now()->toDateString()))
        ->toThrow(DomainException::class);
});

it('forbids a kepala sekolah from supervising a guru at another school', function () {
    $otherSekolah = Sekolah::factory()->forDinas($this->dinas)->create();
    $otherGuru = User::factory()->atSekolah($otherSekolah)->guru()->create();

    expect(fn () => app(AssignSupervisor::class)->handle($this->actor, $this->kepsek, $otherGuru, now()->toDateString()))
        ->toThrow(DomainException::class);
});

it('forbids assigning a guru from a different dinas', function () {
    $otherDinas = Dinas::factory()->create();
    $otherSekolah = Sekolah::factory()->forDinas($otherDinas)->create();
    $pengawas = User::factory()->atSekolah($this->sekolah)->supervisor(SupervisorType::Pengawas)->create();
    $foreignGuru = User::factory()->atSekolah($otherSekolah)->guru()->create();

    expect(fn () => app(AssignSupervisor::class)->handle($this->actor, $pengawas, $foreignGuru, now()->toDateString()))
        ->toThrow(DomainException::class);
});
