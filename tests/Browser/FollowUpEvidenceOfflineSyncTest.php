<?php

declare(strict_types=1);

use App\Domain\FollowUp\Actions\CreateFollowUpPlan;
use App\Models\FollowUpEvidence;
use App\Support\Enums\CycleStatus;
use Pest\Browser\Playwright\Playwright;

beforeEach(fn () => Playwright::setTimeout(15_000));

/**
 * Alur kritis luring→online untuk bukti RTL (checkpoint R-04, ADR-006):
 * guru mencatat bukti pelaksanaan tanpa jaringan lewat
 * resources/js/followup-evidence-outbox.js, lalu tersinkron otomatis begitu
 * koneksi kembali — tanpa duplikasi. Lihat catatan selektor eksplisit di
 * ObservationOfflineSyncTest.php (gotcha pest-plugin-browser v5.0.1).
 */
it('queues RTL evidence while offline and syncs exactly once when back online', function () {
    // fase4Cycle(FollowUpActive) sudah menandai satu-satunya butir 'selesai'
    // (dipakai tes lain untuk skenario plan tertutup, form bukti pun tak
    // tampil lagi karena plan otomatis 'selesai') — bangun sendiri dari
    // FeedbackGiven agar butir masih terbuka untuk tes ini.
    $c = fase4Cycle(CycleStatus::FeedbackGiven);
    $plan = app(CreateFollowUpPlan::class)->handle(
        $c['supervisor'], $c['cycle']->refresh(), 'Tingkatkan partisipasi.', now()->addWeek()->toDateString(),
        [['deskripsi' => 'Butir A', 'indikator_keberhasilan' => 'Indikator A']],
    );
    $item = $plan->items()->sole();

    $page = visit('/login')
        ->fill('[id="form.email"]', $c['guru']->email)
        ->fill('[id="form.password"]', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    $page = $page->navigate("/cycles/{$c['cycle']->id}/follow-up")
        ->assertSee('Pelacak Tindak Lanjut')
        ->assertSee('Siap');

    $page->script("
        Object.defineProperty(navigator, 'onLine', { configurable: true, get: () => false });
        window.dispatchEvent(new Event('offline'));
    ");
    $page->assertSee('Luring');

    $page->fill("[data-testid=\"evidence-input-{$item->id}\"]", 'Sudah dikerjakan sesuai indikator.');
    $page->click("[data-testid=\"evidence-submit-{$item->id}\"]");
    $page->wait(1);

    $page->assertSee('menunggu sinkron');
    $page->assertSee('Luring');
    expect(FollowUpEvidence::where('follow_up_item_id', $item->id)->count())->toBe(0);

    $page->script("
        Object.defineProperty(navigator, 'onLine', { configurable: true, get: () => true });
        window.dispatchEvent(new Event('online'));
    ");

    $page->assertSee('Tersinkron');
    $page->assertDontSee('menunggu sinkron');

    $evidence = FollowUpEvidence::where('follow_up_item_id', $item->id)->sole();
    expect($evidence->deskripsi)->toBe('Sudah dikerjakan sesuai indikator.')
        ->and($evidence->tipe)->toBe('catatan');
})->group('browser');
