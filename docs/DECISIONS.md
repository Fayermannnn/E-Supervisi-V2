# Decision Log & Progress

Catatan keputusan berjalan + status implementasi per story. ADR formal ada di `architecture.md`.

## Keputusan checkpoint (2026-09-09)

| Ref | Keputusan | Sumber |
|---|---|---|
| R-01 | **Bangun Fase 3 sekarang** dengan penanda `@provisional`, perubahan additive-only setelah SLR Gate 6/7. | User checkpoint |
| R-02 | **Multi-dinas via scoping**. Hirarki Dinas(kab/kota)→Sekolah→User. Tanpa level provinsi (additive bila perlu). | User checkpoint |
| R-03 | **API internal-first** `/api/v1` memetakan Spec §8 + endpoint sync PWA. Controller = shell tipis atas Action domain. Tanpa portal dokumentasi publik di MVP. | User checkpoint |
| R-04 | **Offline: konsol Observasi (M2) + antrean bukti RTL (M5)**. Refleksi guru = online. | User checkpoint |
| — | AI default `MockAiProvider`; provider nyata hanya bila kunci API diset. | ADR-009 |
| — | Test DB = PostgreSQL `esupervisi_test` (paritas fitur, bukan SQLite). | testing.md |
| — | Larastan level **8** (bukan `max`). Level max ~90 temuan array-shape pada kode fondasi yang loosely-typed; level 8 tetap menegakkan null-safety, tipe argumen, keberadaan method. Naikkan bertahap. `checkModelProperties` off. | A1/A2 |
| — | Pest 5 + PHPUnit 13 (Pest 4 tidak kompatibel Laravel 13 + PHPUnit 12 di lingkungan ini). `laravel/pao` dihapus (konflik Pest). | A1 |
| — | Peran & Permission disimpan di **kode** (`Role`/`Permission` enum + `RolePermissionMap`), bukan tabel — 4 peran tetap, tak berubah saat runtime pada MVP. Diaudit via version control + test matriks. Tabel `role_assignments` menyimpan pemberian peran (dgn lingkup dinas + audit). | A3 |
| — | `RoleAssignment` memakai trait `Auditable` (auto-audit) — perubahan peran = jejak forensik prioritas. | A3 |
| — | Auth: komponen Livewire kustom (pola Breeze form-object + RateLimiter), bukan Fortify/Breeze scaffold — kontrol penuh atas UI institusional. | A2 |
| — | `Model::shouldBeStrict()` aktif di non-produksi → factory & Action wajib melengkapi kolom nullable. | A2 |
| — | Slide-over pakai `@entangle($wireModel)` via prop eksplisit (bukan `$attributes->wire()`). | A5 |

## Progres implementasi

Legend: ⬜ belum · 🟡 berjalan · ✅ selesai (DoD) · ⏸️ ditunda

### PHASE 0 — Discovery & Architecture ✅
- ✅ Inspeksi repo & lingkungan, ekstraksi spesifikasi
- ✅ 10 artefak Phase 0 (`docs/`)
- ✅ Checkpoint R-01..R-04 dijawab user

### PHASE 1 — Fondasi (M13–M17) ✅ (menunggu review checkpoint)

| Story | Status | Catatan |
|---|---|---|
| A1 Skeleton & tooling | ✅ | Laravel 13 + Postgres + Pest 5 + Livewire 3 + Tailwind 4 + Larastan 8 + Pint strict; `composer ci` hijau; domain skeleton; `config/ai.php`; API (Sanctum) |
| A2 Autentikasi | ✅ | Login (throttle 5×/60s + Lockout audit), logout, lupa/atur-ulang sandi, verifikasi email, blokir user nonaktif |
| A3 RBAC & Policy foundation | ✅ | `Role`/`Permission` enum + `RolePermissionMap` + Gate per permission + 8 Policy; matriks `rbac.md` sbg test parametrik (27 sel + gate) |
| A4 Struktur organisasi | ✅ | CRUD `dinas` & `sekolah` (Livewire, scoped), audit |
| A5 Manajemen pengguna | ✅ | CRUD user, assign role/sekolah/tipe supervisor, aktif/nonaktif (tak bisa diri sendiri), kirim tautan reset; Action `CreateUser`/`UpdateUser` |
| A6 Penugasan supervisor–guru | ✅ | Action `AssignSupervisor` — cek overlap, lingkup kepsek/pengawas, lintas dinas; Livewire CRUD |
| A7 Audit log append-only | ✅ | Tabel tanpa updated_at/deleted_at, model memblok update/delete, `AuditLogger`, trait `Auditable`, listener auth events, viewer read-only scoped |
| A8 Notifikasi foundation | ✅ | `notifications` + `reminder_schedules`, `DispatchesReminders` + command + schedule, bell Livewire, preferensi kanal per user |
| A9 Konfigurasi & kebijakan | ✅ | `PolicySettings` (default global → override dinas, cache), `policy_settings` (partial-unique global), Livewire admin |
| A10 Bantuan & dukungan | ✅ | `help_articles` (markdown), `support_tickets` + notifikasi ke admin sistem, Livewire |
| A11 Shell layout & design system | ✅ | Layout auth + app, sidebar/topbar role-aware, tema terang/gelap/sistem, komponen `x-ui.*` + `x-app.*`, dasbor per peran |
| Seed data fondasi | ✅ | 1 admin sistem, 1 admin dinas, 2 dinas, 6 sekolah, 6 kepsek, 18 guru, 2 pengawas, 22 penugasan, 3 artikel bantuan |

**Tes:** 85 pass / 194 assertions. `composer ci` hijau (Pint + PHPStan 8 + Pest).
**Diverifikasi di browser:** login, dasbor (4 peran), manajemen pengguna + slide-over, organisasi. Konsol bersih.

### PHASE 2 — Inti Siklus: Perencanaan → Observasi (M1, M2, M8) ✅ (menunggu review)

| Story | Status | Catatan |
|---|---|---|
| B1 Bank instrumen (M8) | ✅ | `instruments` + `instrument_versions` (schema JSON + scoring config), `InstrumentSchema` VO + validator, `ItemType`/`InstrumentStatus` enum, `FormatBTemplate` (contoh), Livewire index + editor JSON + pratinjau, InstrumentPolicy |
| B2 Buat siklus (M1) | ✅ | `SupervisionCycle` + `CycleStatus` enum (10) + `CreateCycle` action (cek binaan aktif, dinas_id dari guru); Livewire + API `POST /cycles` pakai Action sama |
| State machine | ✅ | `CycleStateMachine` — map transisi + guard + otorisasi peran + transaksi (status + `cycle_status_transitions` append-only + audit + event `CycleTransitioned`); Fase 3 transisi diblokir eksplisit; AI/null-actor tak bisa memicu transisi manusia |
| B3 Perencanaan & kesepakatan (M1) | ✅ | `PlanningAgreement`, `SavePlanningAgreement` (ubah fokus/instrumen → reset persetujuan), `RecordPlanningAgreementConsent` (kedua pihak setuju → auto transisi DRAFT→SCHEDULED + notifikasi guru); Livewire `PlanningEditor` |
| B4 Refleksi guru (M1) | ✅ | `TeacherReflection`, `SubmitReflection`, di `CycleShow` |
| B5 Konsol observasi online (M2) | ✅ | `Observation`/`ObservationResponse` (EAV), `StartObservation`/`SaveObservation`, konsol Livewire render dari schema, Likert/boolean/text |
| B6 Observasi offline + sync (M2) | ✅ | `observation-console.js` — IndexedDB (state + outbox) + retry 20s + online/offline events + deteksi konflik + resolusi manual; `SyncObservations` action (idempoten via UUID + hash payload, optimistic lock `version`, `observation_sync_log`); endpoint `POST /api/v1/sync/observations` (`ability:observation:sync`), `GET /sync/bootstrap`; Service Worker `public/sw.js` + manifest + `/offline`; `<x-ui.sync-indicator>` |
| B7 Finalisasi observasi (M2) | ✅ | `FinalizeObservation` — validasi semua item wajib, kunci → transisi SCHEDULED→OBSERVATION_DONE |
| B8 Unggah media (M2) | ✅ | Disk privat `observation_media` (luar web root), `UploadObservationMediaRequest` (mime/size dari `policy_settings`), `POST /observations/{id}/media`, `observation_media` table |
| B9/B10 Dasbor | ✅ | Dasbor supervisor/guru menampilkan hitungan siklus nyata (aktif, menunggu observasi, perlu tindak lanjut); `CycleIndex` dengan stepper + filter status |
| API `/api/v1` | ✅ | Memetakan Spec §8: cycles CRUD/schedule/cancel/reflections, observations start/update/finalize/media, sync bootstrap/observations/status, auth token (ADR-007). Controller = shell atas Action. Sanctum stateful (SPA) + bearer (device). Envelope `{data, meta}` / `{errors}`. |
| Seed | ✅ | 1 instrumen Format B (contoh), 4 siklus (1 Draf, 1 Terjadwal, 2 Terjadwal+observasi terisi) |

**Tes:** 115 pass / 269 assertions (30 baru: state machine, InstrumentSchema, cycle flow end-to-end, **sync idempotency + 409 konflik**, cross-supervisor/guru access, admin-dinas detail gating, API envelope/auth). `composer ci` hijau.
**Browser-verified:** login → daftar siklus → detail siklus (stepper, kesepakatan, konsen ✓) → konsol observasi (schema-driven, Likert, autosave → IndexedDB → sync `synced`). Konsol JS bersih.

**Catatan:**
- Revert `Date::use(CarbonImmutable)` — friksi dengan inferensi tipe Larastan tak sepadan.
- Sanctum `abilities`/`ability` middleware alias didaftarkan manual di `bootstrap/app.php` (tidak auto-register di Laravel 12+).
- Transisi Fase 3 (M3–M6) di state machine sudah dipetakan tapi guard-nya melempar "Fase 3" sampai domain tsb dibangun.

### PHASE 3–5
Belum dimulai. Lihat `roadmap.md`. Berikutnya: Fase 3 (M3, M4, M5, M6, M18) — `@provisional`.
