# Offline-First — Strategi (DRAFT, diperdalam di Fase 2)

Sumber: Spec §3.2, §9, §13; master prompt §13. Cakupan MVP: **konsol Observasi (M2)** + **unggah bukti RTL (M5)** (lihat `risk-register.md` R-04).

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
6. **Media**: `POST /api/v1/sync/follow-up-evidence` & `/observations/{id}/media` saat online.

## Invarian (diuji)

- Tidak ada kehilangan data: entri `outbox` hanya dihapus setelah 2xx server.
- Tidak ada duplikasi: retry ganda pada koneksi buruk → satu baris DB.
- Tidak ada auto-merge diam-diam: konflik selalu keputusan manusia.
- Finalisasi observasi hanya saat online + semua item `required` terisi.

## Known limitations (didokumentasikan, bukan disembunyikan)

- Video observasi tidak tersedia untuk diputar luring; hanya antre unggah.
- Bootstrap butuh koneksi minimal satu kali sebelum ke lapangan.
- Push notification luring tidak dijamin di semua browser mobile.
