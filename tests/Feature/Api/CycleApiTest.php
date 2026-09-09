<?php

declare(strict_types=1);

use App\Models\Dinas;
use App\Models\Sekolah;
use App\Models\SupervisionCycle;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\SupervisorType;
use Laravel\Sanctum\Sanctum;

it('requires authentication for the cycle list', function () {
    $this->getJson('/api/v1/cycles')->assertUnauthorized();
});

it('returns a consistent envelope for the scoped cycle list', function () {
    $cycle = SupervisionCycle::factory()->create()->load('supervisor');
    Sanctum::actingAs($cycle->supervisor);

    $this->getJson('/api/v1/cycles')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'judul', 'status', 'status_label']], 'meta' => ['page', 'total']])
        ->assertJsonPath('data.0.id', $cycle->id);
});

it('creates a cycle via the API for a binaan and 422s for a stranger', function () {
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();
    $supervisor = User::factory()->atSekolah($sekolah)->supervisor(SupervisorType::KepalaSekolah)->create();
    $guru = User::factory()->atSekolah($sekolah)->guru()->create();
    SupervisorAssignment::create(['supervisor_id' => $supervisor->id, 'guru_id' => $guru->id, 'mulai' => now()->subMonth()->toDateString()]);
    $stranger = User::factory()->guru()->create();

    Sanctum::actingAs($supervisor);

    $this->postJson('/api/v1/cycles', [
        'guru_id' => $stranger->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'judul' => 'X',
    ])->assertStatus(422)->assertJsonPath('errors.guru_id.0', 'Guru tersebut bukan binaan aktif Anda.');

    $this->postJson('/api/v1/cycles', [
        'guru_id' => $guru->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'judul' => 'Uji',
    ])->assertCreated()->assertJsonPath('data.status', 0);
});

it('forbids a guru from creating a cycle', function () {
    $guru = User::factory()->guru()->create();
    Sanctum::actingAs($guru);

    $this->postJson('/api/v1/cycles', [
        'guru_id' => $guru->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'judul' => 'X',
    ])->assertForbidden();
});
