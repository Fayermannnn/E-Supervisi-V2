<?php

declare(strict_types=1);

use App\Models\Observation;
use App\Models\ObservationResponse;
use App\Support\Enums\CycleStatus;
use Pest\Browser\Playwright\Playwright;

beforeEach(fn () => Playwright::setTimeout(15_000));

/**
 * Alur kritis luring→online (docs/testing.md, master prompt §17): supervisor
 * mengisi konsol observasi tanpa jaringan, item diantre ke IndexedDB
 * (`observation-console.js`), lalu tersinkron otomatis begitu koneksi
 * kembali — tanpa duplikasi (ADR-006, risk T-01).
 *
 * Browser sungguhan (Chromium via Pest\Browser + Playwright) — bukan mock.
 * "Luring" disimulasikan lewat override `navigator.onLine` + event
 * online/offline (mekanisme deteksi konektivitas nyata yang dipakai
 * `observation-console.js`); tidak ada primitif "put page offline" di
 * pest-plugin-browser v5, jadi ini pendekatan paling akurat yang tersedia.
 *
 * PENTING — pakai selektor CSS eksplisit (`[id="…"]`, `[data-testid="…"]`),
 * BUKAN nama/id polos (`'form.email'`). Ditemukan lewat investigasi: jalur
 * "tebak" non-eksplisit `GuessLocator` (dipakai saat selector bukan diawali
 * `#`/`.`/`[`) macet tanpa akhir (~30 dtk lalu timeout) pada
 * pestphp/pest-plugin-browser v5.0.1 + playwright npm v1.63.0 — walau
 * `[id="…"]` eksplisit ke elemen yang SAMA sukses instan. Reproduksi
 * minimal: `visit('/login')->fill('form.email', 'x')` macet;
 * `visit('/login')->fill('[id="form.email"]', 'x')` sukses <1 dtk. Klik
 * berbasis teks (`click('Masuk')`) tidak terpengaruh — hanya lookup
 * berbasis `[id]`/`[name]` yang bermasalah. Cek ulang bila plugin di-upgrade.
 */
it('queues an observation score while offline and syncs exactly once when back online', function () {
    $c = fase4Cycle(CycleStatus::Scheduled);

    $page = visit('/login')
        ->fill('[id="form.email"]', $c['supervisor']->email)
        ->fill('[id="form.password"]', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    $page = $page->navigate("/cycles/{$c['cycle']->id}/observe")
        ->assertSee('Konsol Observasi')
        ->assertSee('Siap');

    // Putuskan "jaringan" dari sudut pandang aplikasi (navigator.onLine +
    // event asli yang didengarkan observation-console.js).
    $page->script("
        Object.defineProperty(navigator, 'onLine', { configurable: true, get: () => false });
        window.dispatchEvent(new Event('offline'));
    ");
    $page->assertSee('Luring');

    // Isi satu item (skor 'apersepsi') dua kali sambil luring — simulasikan
    // supervisor menyunting beberapa kali sebelum koneksi kembali.
    $page->click('[data-testid="score-apersepsi-2"]');
    $page->wait(1);
    $page->click('[data-testid="score-apersepsi-3"]');
    $page->wait(1);

    expect((int) $page->script("window.Alpine.store('obs').pendingCount"))->toBeGreaterThan(0);
    $page->assertSee('Luring'); // tetap luring -- flush() tidak pernah dicoba selagi offline

    expect(ObservationResponse::whereHas('observation', fn ($q) => $q->where('cycle_id', $c['cycle']->id))->count())
        ->toBe(0); // belum ada apa pun yang sampai ke server

    // Jaringan kembali.
    $page->script("
        Object.defineProperty(navigator, 'onLine', { configurable: true, get: () => true });
        window.dispatchEvent(new Event('online'));
    ");

    $page->assertSee('Tersinkron');

    $observation = Observation::where('cycle_id', $c['cycle']->id)->sole();
    $response = ObservationResponse::where('observation_id', $observation->id)
        ->where('item_key', 'apersepsi')
        ->sole();

    expect((float) $response->value_numeric)->toBe(3.0) // nilai TERAKHIR, bukan duplikat dari klik pertama
        ->and((int) $page->script("window.Alpine.store('obs').pendingCount"))->toBe(0);
})->group('browser');
