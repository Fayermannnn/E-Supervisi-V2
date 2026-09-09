# Deployment (DRAFT — diperdalam di Fase 5)

Sumber: Spec §3.1 (infrastruktur terbatas), §10 (keamanan/PDP); master prompt §26.

## Lingkungan pengembangan (lokal)

- **Laravel Herd** (PHP 8.4) — domain `e-supervisi.test`.
- **PostgreSQL 16** lokal (`127.0.0.1:5432`), database `esupervisi`, `esupervisi_test`.
- Node 20+ untuk Vite/Workbox build.
- `php artisan queue:work` (driver `database` di dev) untuk job AI, notifikasi, ekspor, sync media.
- Scheduler: `php artisan schedule:work` untuk job harian (deteksi RTL terlambat, reminder, arsip).

Setup:
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build   # atau: npm run dev
```

## Produksi (target awal — VPS tunggal, cocok konteks 3T)

| Komponen | Pilihan |
|---|---|
| Web | Nginx + PHP-FPM 8.3/8.4 |
| App | Laravel (opcache on), `php artisan optimize` |
| DB | PostgreSQL 16, koneksi TLS, backup harian `pg_dump` + retensi 30 hari + uji restore triwulan |
| Queue | `database` (awal) → Redis bila beban naik; Supervisor mengelola `queue:work` |
| Scheduler | cron `* * * * * php artisan schedule:run` |
| Storage berkas | disk lokal privat di luar web root; **enkripsi at-rest** untuk media observasi & dokumen (Spec §10); akses via signed route + Policy |
| TLS | wajib (Let's Encrypt); HSTS; secure headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) |
| PWA | HTTPS wajib untuk Service Worker |

## Keamanan operasional

- `.env` tidak di-commit; secret via environment.
- Rate limiting: login, endpoint sync, endpoint AI (terpisah).
- `APP_DEBUG=false`; halaman error kustom.
- Audit log append-only; job arsip ke cold storage, bukan hapus.
- Sanctum token PWA: kedaluwarsa + pencabutan per device.

## Kepatuhan UU 27/2022 (PDP) — checklist Fase 5

- [ ] Dokumen basis pemrosesan data (pelaksanaan tugas dinas pendidikan).
- [ ] Kebijakan retensi & arsip (bukan hapus otomatis).
- [ ] Enkripsi transit (TLS) + at-rest (berkas observasi).
- [ ] Daftar data pribadi yang diproses + peran (controller/processor).
- [ ] Prosedur permintaan akses/koreksi data oleh guru.
- [ ] Audit trail perubahan data sensitif.
- [ ] Review oleh pihak berkompeten sebelum go-live.

## Rilis

- Tag per fase (`phase1-complete`, ...).
- Migrasi non-destruktif; migrasi destruktif butuh konfirmasi eksplisit (RULE 8) + backup pra-migrasi.
- Rollback plan per rilis (down migration teruji di staging).
