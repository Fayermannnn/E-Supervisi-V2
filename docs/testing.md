# Strategi Pengujian

Sumber: master prompt §17, §22, §26. Runner: **Pest**. DB test: PostgreSQL (bukan SQLite — paritas fitur jsonb/enum). `phpunit.xml` menyetel `memory_limit=512M` (arch test).

**Status:** 239 tes / 645 assertions, `composer ci` hijau (Pint + Larastan 8 + Pest) — termasuk ekspor laporan server-side + header keamanan respons (`SecureHeaders`) + visualisasi data (`x-ui.meter`/`x-ui.bar-distribution`) pasca-Fase 5. Line coverage tidak dilaporkan (tanpa Xdebug/PCOV) — inventaris per kategori di `docs/technical-evaluation.md`.

## Piramida

| Lapisan | Cakupan | Lokasi |
|---|---|---|
| Unit | State transition & guard, skoring instrumen, deteksi keterlambatan RTL (batas tanggal), status draft AI, value objects/enums | `tests/Unit` |
| Feature | Login, buat siklus, perencanaan, observasi, sync (idempoten/konflik), analisis, umpan balik, tindak lanjut, pelaporan, ekspor | `tests/Feature` |
| Security | Privilege escalation, IDOR, cross-school/cross-dinas access, unauthorized API, audit log immutability, upload file berbahaya, header keamanan respons (CSP nonce+hash, HSTS, X-Frame-Options, Referrer/Permissions-Policy) | `tests/Feature/Security` |
| Architecture | Larangan import lintas domain, audit log tanpa route tulis, AI tanpa akses DB | `tests/Arch` (pest-arch) |
| Performance | Batas query + wall-clock jalur baca (dasbor, laporan agregat) pada ~200 siklus; verifikasi index | `tests/Feature/Performance` |
| Browser | Alur kritis Guru & Supervisor (termasuk skenario luring→online) — **belum diotomasi** | `tests/Browser` |
| Static | Larastan level 8, Pint (strict types) | CI |

**Modul Fase 4–5 yang diuji:** program M7 (generate/idempotensi/scoping), rekomendasi PKB M9 (`PkbMatcher`, pola berulang), praktik baik M10 (nominate→consent→curate + gate), 360° M11 (timing + ambang anonimitas + tak memicu transisi), kalibrasi M12 (`CalibrationStats`, sesi end-to-end), evaluasi ahli Fase 5 (`ExpertJudgmentStats` CVR/Aiken/SUS, alur panel).

## Kasus wajib per master prompt §17

**Unit:** transisi status valid/invalid; skoring deterministik; RTL `tenggat < today` → overdue; RTL bukti masuk → kembali berjalan; output AI selalu `draft`.

**Feature:** setiap endpoint Spec §8 (validasi, otorisasi, kode HTTP, envelope); sync idempoten (retry ganda → 1 baris); konflik → 409.

**Security:**
- guru A tak bisa `GET /cycles/{siklus guru B}` → 403 + audit;
- pengawas tak bisa akses guru di luar `supervisor_assignments` meski satu dinas;
- `admin_dinas` X tak bisa lihat data dinas Y;
- `PATCH`/`DELETE` ke `audit_logs` → 404/405 (tidak ada route);
- upload `.php`/`.svg` berbahaya ditolak;
- token sync scope `observation:sync` tak bisa memanggil endpoint admin.

**Browser:**
- Guru: login → jadwal → refleksi → hasil observasi → konfirmasi umpan balik → unggah bukti RTL.
- Supervisor: login → buat siklus → perencanaan → observasi (matikan jaringan, isi, nyalakan, verifikasi sinkron tanpa duplikat) → analisis (tinjau & sunting draft AI) → umpan balik → RTL → laporan.

## Data test

Factory per model; `SeedDemoData` untuk skenario end-to-end. Data dummy jelas bukan data nyata (nama fiktif, NIP pola `9999...`).

## Gate CI (lokal script `composer ci`)

`pint --test` → `phpstan analyse` (max) → `pest --coverage` (target: domain inti ≥ 80%) → `pest --group=arch` → (opsional) `pest --group=browser`.

Setiap laporan fase menyertakan output ringkas test + angka coverage + daftar known limitations.
