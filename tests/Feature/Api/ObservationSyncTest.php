<?php

declare(strict_types=1);

use App\Domain\Planning\Actions\RecordPlanningAgreementConsent;
use App\Domain\Planning\Actions\SavePlanningAgreement;
use App\Domain\Supervision\Actions\CreateCycle;
use App\Models\Dinas;
use App\Models\Instrument;
use App\Models\Sekolah;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\SupervisorType;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();
    $this->supervisor = User::factory()->atSekolah($sekolah)->supervisor(SupervisorType::KepalaSekolah)->create();
    $guru = User::factory()->atSekolah($sekolah)->guru()->create();
    SupervisorAssignment::create(['supervisor_id' => $this->supervisor->id, 'guru_id' => $guru->id, 'mulai' => now()->subMonth()->toDateString()]);
    $this->version = Instrument::factory()->published()->create()->versions()->first();

    $this->cycle = app(CreateCycle::class)->handle($this->supervisor, $guru, '2026/2027', 'ganjil', 'Uji');
    app(SavePlanningAgreement::class)->handle($this->supervisor, $this->cycle, [
        'fokus_observasi' => 'Fokus', 'instrument_version_id' => $this->version->id,
        'tipe_observasi' => 'sinkron', 'jadwal_mulai' => now()->addDay()->toDateTimeString(),
    ]);
    app(RecordPlanningAgreementConsent::class)->handle($guru, $this->cycle);
    app(RecordPlanningAgreementConsent::class)->handle($this->supervisor, $this->cycle);
    $this->cycle->refresh();

    $this->obsId = Str::uuid7()->toString();
});

function syncPayload(string $obsId, string $cycleId, ?int $baseVersion, array $responses): array
{
    return [
        'observations' => [[
            'id' => $obsId,
            'cycle_id' => $cycleId,
            'base_version' => $baseVersion,
            'device_id' => 'test-device',
            'responses' => $responses,
            'catatan_skrip' => 'catatan',
        ]],
    ];
}

it('rejects sync without the observation:sync ability', function () {
    Sanctum::actingAs($this->supervisor, ['other:scope']);

    $this->postJson('/api/v1/sync/observations', syncPayload($this->obsId, $this->cycle->id, null, []))
        ->assertForbidden();
});

it('applies a batch and creates the observation with the client-supplied UUID', function () {
    Sanctum::actingAs($this->supervisor, ['observation:sync']);

    $res = $this->postJson('/api/v1/sync/observations', syncPayload($this->obsId, $this->cycle->id, null, [
        ['item_key' => 'apersepsi', 'section_key' => 'pendahuluan', 'value' => 3],
    ]));

    $res->assertOk()->assertJsonPath('data.applied.0.id', $this->obsId);
    $this->assertDatabaseHas('observations', ['id' => $this->obsId, 'cycle_id' => $this->cycle->id]);
    $this->assertDatabaseCount('observation_responses', 1);
});

it('is idempotent — re-sending the identical payload does not duplicate responses', function () {
    Sanctum::actingAs($this->supervisor, ['observation:sync']);
    $payload = syncPayload($this->obsId, $this->cycle->id, null, [
        ['item_key' => 'apersepsi', 'section_key' => 'pendahuluan', 'value' => 3],
    ]);

    $this->postJson('/api/v1/sync/observations', $payload)->assertOk();
    $this->postJson('/api/v1/sync/observations', $payload)->assertOk();
    $this->postJson('/api/v1/sync/observations', $payload)->assertOk();

    $this->assertDatabaseCount('observation_responses', 1);
    expect(App\Models\Observation::find($this->obsId)->version)->toBe(2); // hanya satu penerapan sungguhan
});

it('returns HTTP 409 with the server state when the client base version is stale', function () {
    Sanctum::actingAs($this->supervisor, ['observation:sync']);

    // v1 -> v2
    $this->postJson('/api/v1/sync/observations', syncPayload($this->obsId, $this->cycle->id, 1, [
        ['item_key' => 'apersepsi', 'section_key' => 'pendahuluan', 'value' => 3],
    ]))->assertOk();

    // Second device still thinks base is v1
    $res = $this->postJson('/api/v1/sync/observations', syncPayload($this->obsId, $this->cycle->id, 1, [
        ['item_key' => 'apersepsi', 'section_key' => 'pendahuluan', 'value' => 4],
    ]));

    $res->assertOk()->assertJsonPath('data.conflicts.0.id', $this->obsId);
    $this->assertDatabaseHas('observation_sync_log', ['observation_id' => $this->obsId, 'action' => 'conflict', 'resolved' => false]);
});

it('does not let a supervisor sync a cycle that is not theirs', function () {
    $other = User::factory()->supervisor()->create();
    Sanctum::actingAs($other, ['observation:sync']);

    $this->postJson('/api/v1/sync/observations', syncPayload($this->obsId, $this->cycle->id, null, []))
        ->assertForbidden();
});
