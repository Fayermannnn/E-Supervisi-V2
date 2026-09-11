<?php

declare(strict_types=1);

use App\Domain\FollowUp\Actions\CreateFollowUpPlan;
use App\Models\FollowUpEvidence;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * POST /api/v1/sync/follow-up-evidence — outbox luring bukti RTL
 * (resources/js/followup-evidence-outbox.js, ADR-006, checkpoint R-04).
 */
beforeEach(function () {
    // fase4Cycle(FollowUpActive) sudah menandai satu-satunya butir 'selesai'
    // (dipakai tes lain untuk skenario plan tertutup) — bangun sendiri dari
    // FeedbackGiven agar butir masih terbuka ('belum') untuk tes ini.
    $c = fase4Cycle(CycleStatus::FeedbackGiven);
    $this->guru = $c['guru'];
    $this->supervisor = $c['supervisor'];

    $plan = app(CreateFollowUpPlan::class)->handle(
        $c['supervisor'], $c['cycle']->refresh(), 'Tingkatkan partisipasi.', now()->addWeek()->toDateString(),
        [['deskripsi' => 'Butir A', 'indikator_keberhasilan' => 'Indikator A']],
    );
    $this->item = $plan->items()->sole();
    $this->evidenceId = Str::uuid7()->toString();
});

function evidenceSyncPayload(string $id, string $itemId, string $deskripsi): array
{
    return ['evidence' => [[
        'id' => $id,
        'follow_up_item_id' => $itemId,
        'tipe' => 'catatan',
        'deskripsi' => $deskripsi,
    ]]];
}

it('rejects sync without the follow-up:evidence ability', function () {
    Sanctum::actingAs($this->guru, ['other:scope']);

    $this->postJson('/api/v1/sync/follow-up-evidence', evidenceSyncPayload($this->evidenceId, $this->item->id, 'Sudah dikerjakan'))
        ->assertForbidden();
});

it('applies a batch and stores the evidence with the client-supplied UUID', function () {
    Sanctum::actingAs($this->guru, ['follow-up:evidence']);

    $res = $this->postJson('/api/v1/sync/follow-up-evidence', evidenceSyncPayload($this->evidenceId, $this->item->id, 'Sudah dikerjakan'));

    $res->assertOk()->assertJsonPath('data.applied.0.id', $this->evidenceId);
    $this->assertDatabaseHas('follow_up_evidence', [
        'id' => $this->evidenceId,
        'follow_up_item_id' => $this->item->id,
        'deskripsi' => 'Sudah dikerjakan',
    ]);
    expect($this->item->refresh()->status)->toBe('berjalan'); // belum -> berjalan saat bukti pertama masuk
});

it('is idempotent — re-sending the identical batch does not duplicate the evidence row', function () {
    Sanctum::actingAs($this->guru, ['follow-up:evidence']);
    $payload = evidenceSyncPayload($this->evidenceId, $this->item->id, 'Sudah dikerjakan');

    $this->postJson('/api/v1/sync/follow-up-evidence', $payload)->assertOk();
    $this->postJson('/api/v1/sync/follow-up-evidence', $payload)->assertOk();
    $this->postJson('/api/v1/sync/follow-up-evidence', $payload)->assertOk();

    expect(FollowUpEvidence::where('follow_up_item_id', $this->item->id)->count())->toBe(1);
});

it('reports a failed entry without discarding the rest of the batch', function () {
    Sanctum::actingAs($this->guru, ['follow-up:evidence']);
    $bogusItemId = (string) Str::uuid7();

    $res = $this->postJson('/api/v1/sync/follow-up-evidence', [
        'evidence' => [
            ['id' => (string) Str::uuid7(), 'follow_up_item_id' => $bogusItemId, 'tipe' => 'catatan', 'deskripsi' => 'X'],
            ['id' => $this->evidenceId, 'follow_up_item_id' => $this->item->id, 'tipe' => 'catatan', 'deskripsi' => 'Sah'],
        ],
    ]);

    $res->assertOk()
        ->assertJsonCount(1, 'data.applied')
        ->assertJsonCount(1, 'data.failed')
        ->assertJsonPath('data.applied.0.id', $this->evidenceId);
});

it('does not let another teacher submit evidence for a cycle that is not theirs', function () {
    $stranger = User::factory()->guru()->create();
    Sanctum::actingAs($stranger, ['follow-up:evidence']);

    $res = $this->postJson('/api/v1/sync/follow-up-evidence', evidenceSyncPayload($this->evidenceId, $this->item->id, 'X'));

    $res->assertOk()->assertJsonCount(0, 'data.applied')->assertJsonCount(1, 'data.failed');
    $this->assertDatabaseMissing('follow_up_evidence', ['id' => $this->evidenceId]);
});
