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
| Queue | `database` (awal) → Redis bila beban naik; Supervisor mengelola `queue:work`. **Wajib jalan** — ekspor laporan (`GenerateReportExport`), AI, notifikasi, sync media semuanya queued. |
| Scheduler | cron `* * * * * php artisan schedule:run` |
| Storage berkas | disk lokal privat di luar web root (media observasi: `observation-media/`; berkas ekspor laporan: `private/reports/`); **enkripsi at-rest** untuk media observasi & dokumen (Spec §10); akses via route + Policy |
| TLS | wajib (Let's Encrypt); HSTS; secure headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) |
| PWA | HTTPS wajib untuk Service Worker |

## Keamanan operasional

- `.env` tidak di-commit; secret via environment.
- Rate limiting: login, endpoint sync, endpoint AI (terpisah).
- `APP_DEBUG=false`; halaman error kustom.
- Audit log append-only; job arsip ke cold storage, bukan hapus.
- Sanctum token PWA: kedaluwarsa + pencabutan per device.

### Header keamanan respons (aplikasi — sudah terpasang)

`App\Http\Middleware\SecureHeaders` (global) menegakkan pada **setiap** respons —
konfigurasi `config/security.php`, toggle via env (lihat ADR-016):

| Header | Nilai |
|---|---|
| `Content-Security-Policy` | `default-src 'self'`; `script-src 'self' 'unsafe-eval' 'nonce-…' <hash>`; `style-src 'self' 'unsafe-inline'`; `object-src 'none'`; `base-uri 'self'`; `form-action 'self'`; `frame-ancestors 'none'`; `img-src 'self' data:` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` — **hanya saat HTTPS**; aktifkan `SECURITY_HSTS_ENABLED=true` di produksi |
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | kamera/mikrofon/geolokasi/pembayaran/USB dimatikan |
| `Cross-Origin-Opener-Policy` / `Cross-Origin-Resource-Policy` | `same-origin` |

**Env produksi:**
```
SECURITY_CSP_ENABLED=true
SECURITY_CSP_REPORT_ONLY=false     # true saat memantau pelanggaran sebelum menegakkan
SECURITY_HSTS_ENABLED=true         # setelah TLS + seluruh subdomain HTTPS
# SECURITY_CSP_REPORT_URI=https://…  # opsional: endpoint laporan pelanggaran
```

**Di belakang reverse proxy** (Nginx TLS termination): konfigurasi
`TrustProxies` (`bootstrap/app.php` → `$middleware->trustProxies(...)`) agar
`$request->secure()` benar sehingga HSTS terkirim.

Nginx boleh menambah header duplikat/pelengkap (mis. `X-Frame-Options`), tapi
tidak wajib — aplikasi sudah menegakkan.

## Kepatuhan UU 27/2022 (PDP) — checklist pra-go-live

Status per `tag phase5-complete` (lihat juga `docs/technical-evaluation.md` §1):

- [ ] Dokumen basis pemrosesan data (pelaksanaan tugas dinas pendidikan) — **belum**, review hukum.
- [x] Kebijakan retensi & arsip (bukan hapus otomatis) — `ArchiveReportedCyclesCommand`; `audit_logs` append-only.
- [~] Enkripsi transit (TLS) + at-rest (berkas observasi) — TLS: HSTS + secure
  headers ditegakkan aplikasi (`SecureHeaders`, ADR-016); sertifikat + enkripsi
  at-rest = konfigurasi deployment.
- [~] Daftar data pribadi yang diproses + peran — `rbac.md` + `docs/dsr-artefak.md`; formalisasi dokumen hukum belum.
- [ ] Prosedur permintaan akses/koreksi data oleh guru — UI profil ada; prosedur formal belum.
- [x] Audit trail perubahan data sensitif — `AuditLogger` + trait `Auditable` + `CycleStateMachine`; diuji immutability.
- [x] Persetujuan (consent) sebelum mempublikasi praktik pembelajaran guru — `RespondBestPracticeConsent` (M10); penilaian 360° anonim di atas ambang (M11).
- [ ] Review oleh pihak berkompeten sebelum go-live.

Kontrol teknis yang **sudah** ada: RBAC default-deny + global scope + suite security (IDOR/cross-dinas), rate-limit login/sync, `APP_DEBUG=false` + halaman error kustom, token PWA scoped + dapat dicabut, **header keamanan respons (CSP nonce+hash, HSTS, X-Frame-Options, dst.) via `SecureHeaders` middleware** (ADR-016).

## Rilis

- Tag per fase (`phase1-complete`, ...).
- Migrasi non-destruktif; migrasi destruktif butuh konfirmasi eksplisit (RULE 8) + backup pra-migrasi.
- Rollback plan per rilis (down migration teruji di staging).
