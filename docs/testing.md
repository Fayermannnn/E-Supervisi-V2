# Strategi Pengujian (DRAFT)

Sumber: master prompt §17, §22, §26. Runner: **Pest**. DB test: PostgreSQL (bukan SQLite — paritas fitur jsonb/enum). Browser: Laravel Dusk atau Playwright.

## Piramida

| Lapisan | Cakupan | Lokasi |
|---|---|---|
| Unit | State transition & guard, skoring instrumen, deteksi keterlambatan RTL (batas tanggal), status draft AI, value objects/enums | `tests/Unit` |
| Feature | Login, buat siklus, perencanaan, observasi, sync (idempoten/konflik), analisis, umpan balik, tindak lanjut, pelaporan, ekspor | `tests/Feature` |
| Security | Privilege escalation, IDOR, cross-school/cross-dinas access, unauthorized API, audit log immutability, upload file berbahaya | `tests/Feature/Security` |
| Architecture | Larangan import lintas domain, audit log tanpa route tulis, AI tanpa akses DB | `tests/Arch` (pest-arch) |
| Browser | Alur kritis Guru & Supervisor (termasuk skenario luring→online) | `tests/Browser` |
| Static | Larastan level max, Pint | CI |

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
