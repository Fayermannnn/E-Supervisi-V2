# Offline-First — Strategi (IMPLEMENTED, Fase 2 + pasca-Fase 5)

Sumber: Spec §3.2, §9, §13; master prompt §13. Cakupan MVP: **konsol Observasi (M2)** (selesai, Fase 2) + **bukti RTL (M5)** (selesai, pasca-Fase 5 — lihat checkpoint R-04).

## Implementasi Fase 2 — Observasi

| Bagian | Berkas |
|---|---|
| Modul klien | `resources/js/observation-console.js` — IndexedDB (`state` + `outbox`), debounce autosave, retry tiap 20 dtk, listener `online`/`offline`, deteksi & resolusi konflik |
| Service Worker | `public/sw.js` — precache aset Vite, navigasi network-first + fallback `/offline`, SWR untuk aset statis, API tidak di-cache |
| Manifest PWA | `public/manifest.json` + `public/icon.svg` |
| Endpoint sync | `POST /api/v1/sync/observations` (`ability:observation:sync`), `GET /api/v1/sync/bootstrap`, `GET /api/v1/sync/status` |
| Action server | `App\Domain\Observation\Actions\SyncObservations` — upsert per UUID klien, idempoten via hash payload di `observation_sync_log`, optimistic lock `observations.version` → 409 + state server pada konflik |
| Indikator UI | `<x-ui.sync-indicator>` — `Siap · Luring · Menyimpan lokal · Menunggu sinkron · Menyinkronkan · Tersinkron · Konflik` |

Diuji: `tests/Feature/Api/ObservationSyncTest.php` (idempotensi 3× kirim → 1 baris; konflik base-version → 409 + `observation_sync_log`; scope token; cross-supervisor ditolak) + `tests/Browser/ObservationOfflineSyncTest.php` (Chromium sungguhan — deteksi luring, antre, sinkron otomatis, tanpa duplikat).

## Implementasi pasca-Fase 5 — Bukti RTL

Modul mandiri terpisah dari konsol observasi (domain berbeda, M5 bukan M2 — selaras ADR-006/ADR-012 "kompleksitas Livewire → modul Alpine/JS mandiri").

| Bagian | Berkas |
|---|---|
| Modul klien | `resources/js/followup-evidence-outbox.js` — IndexedDB (`outbox`), listener `online`/`offline`, retry tiap 20 dtk, `$wire.$refresh()` setelah sinkron sukses agar daftar bukti (dirender server) termutakhirkan |
| Endpoint sync | `POST /api/v1/sync/follow-up-evidence` (`ability:follow-up:evidence`) — batch, maks 50 entri per kirim |
| Action server | `App\Domain\FollowUp\Actions\SyncFollowUpEvidence` — mengorkestrasi batch, mendelegasikan tiap entri ke `SubmitFollowUpEvidence` (sudah idempoten via UUID klien sejak awal); satu entri gagal tak menggagalkan entri lain |
| UI | `resources/views/livewire/follow-up/follow-up-tracker.blade.php` — badge sinkron ringkas + baris "Bukti (menunggu sinkron)" lokal per butir sebelum tersinkron |

Diuji: `tests/Feature/Api/FollowUpEvidenceSyncTest.php` (idempotensi, ability gate, batch parsial gagal, cross-guru ditolak) + `tests/Browser/FollowUpEvidenceOfflineSyncTest.php` (Chromium sungguhan).

Cakupan MVP **tidak** termasuk lampiran berkas/foto luring untuk bukti RTL —
hanya catatan teks (`tipe: catatan`); unggah berkas (`dokumen`/`foto`) tetap
API-only, online (lihat T-07 di `risk-register.md`).

> Master prompt §13: **jangan** klaim "offline support" bila hanya menyimpan draft di browser tanpa sinkronisasi sesungguhnya.

## Komponen

| Lapisan | Teknologi | Fungsi |
|---|---|---|
| App shell | Service Worker (Workbox) | Cache HTML/CSS/JS/ikon konsol observasi; `precache` + `staleWhileRevalidate` |
| Data lokal | IndexedDB (via `idb`) | Store: `cycles`, `instrument_versions`, `observations`, `observation_responses`, `outbox`, `media_queue` |
| Antrian kirim | `outbox` object store | Setiap mutasi = entri `{ id, entity, payload, base_version, status, attempts, last_error }` |
| Sinkronisasi | Background Sync API + fallback interval | Flush `outbox` saat online; backoff eksponensial (maks ~5 menit) |
| Konflik | Optimistic lock `version` server | Server 409 bila `base_version` != current → klien tandai `conflict`, tampilkan diff, resolusi manual |
| Media | `media_queue` + resumable upload | Metadata dulu; file diunggah saat online, chunked bila didukung; **tidak** di-cache untuk kerja luring |

## Status yang ditampilkan (`<x-sync-indicator>`)

`Luring` → `Tersimpan lokal` → `Menunggu sinkron` → `Menyinkronkan` → `Tersinkron` (atau `Konflik — perlu tinjauan`)

## Alur

1. **Bootstrap** (`GET /api/v1/sync/bootstrap`): saat online, tarik siklus terjadwal + versi instrumen → IndexedDB.
2. **Kerja luring**: semua perubahan ditulis ke IndexedDB + `outbox` (status `local`).
3. **Online kembali**: Background Sync memicu flush → `POST /api/v1/sync/observations` (batch, idempoten).
4. **Server**: `upsert` berdasarkan UUID klien; unik `(observation_id, item_key)` mencegah duplikat pada retry; kembalikan `version` baru.
5. **Konflik**: 409 + `data.server` → klien simpan sebagai `conflict`, UI resolusi (ambil server / ambil lokal / gabung manual) → `POST /sync/observations/{id}/resolve`.
6. **Bukti RTL** (mandiri, lihat §Implementasi pasca-Fase 5 di atas): catatan diantre luring → `POST /api/v1/sync/follow-up-evidence` (batch, idempoten) begitu online. Lampiran berkas (`/observations/{id}/media`) tetap online-only.

## Invarian (diuji)

- Tidak ada kehilangan data: entri `outbox` hanya dihapus setelah 2xx server.
- Tidak ada duplikasi: retry ganda pada koneksi buruk → satu baris DB.
- Tidak ada auto-merge diam-diam: konflik selalu keputusan manusia.
- Finalisasi observasi hanya saat online + semua item `required` terisi.

## Known limitations (didokumentasikan, bukan disembunyikan)

- Video observasi tidak tersedia untuk diputar luring; hanya antre unggah.
- Bootstrap butuh koneksi minimal satu kali sebelum ke lapangan.
- Push notification luring tidak dijamin di semua browser mobile.
