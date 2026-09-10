# Decision Log & Progress

Catatan keputusan berjalan + status implementasi per story. ADR formal ada di `architecture.md`.

## Keputusan checkpoint Fase 5 (2026-09-10)

| Ref | Keputusan | Sumber |
|---|---|---|
| F5-01 | **Instrumen expert judgment dibangun sebagai modul in-app** (bukan lembar luring + utilitas). Domain `Evaluation` baru + peran `ahli`; ahli login & mengisi CVR/Aiken's V/SUS lewat Livewire; sistem menghitung & menyimpan snapshot agregat. DoD penuh. | User checkpoint |
| F5-02 | **Uji beban ringan = assertion performa di Pest** (dasbor & laporan agregat pada ~200 siklus, batas jumlah query + wall-clock longgar) + prosedur uji beban manual terdokumentasi (`wrk`). Tanpa harness beban mandiri. | User checkpoint |
| — | Line coverage tidak dilaporkan (tanpa Xdebug/PCOV di lingkungan) — diganti inventaris tes per kategori (`docs/technical-evaluation.md`). | Fase 5 |
| — | Peran `ahli` disimpan di kode (`RolePermissionMap`), hanya `ManageOwnProfile` + `SubmitExpertReview`; bukan aktor siklus, tidak dinas/sekolah-scoped. | Fase 5 |

## Keputusan checkpoint Fase 4 (2026-09-10)

| Ref | Keputusan | Sumber |
|---|---|---|
| F4-01 | **M7 program tahunan dimiliki supervisor** (bukan admin dinas). Tanpa `program_assignments`/delegasi pengawas di MVP (additive bila perlu). Siklus di-generate berstatus **DRAFT** via `CreateCycle` — perencanaan, instrumen & kesepakatan tetap manual per siklus. State machine **tidak berubah**. | User checkpoint |
| F4-02 | **M10 Perpustakaan Praktik Baik**: alur nominasi supervisor → **consent guru** (UU PDP) → kurasi Admin Dinas → terbit dinas-wide. Gate skor via `policy_settings['professional_dev.best_practice_min_score']` (default 0.75). Status: `menunggu_consent → menunggu_kurasi → terbit/ditolak/ditarik`. | User checkpoint |
| F4-03 | **M11 360°**: formulir terbuka sejak `FEEDBACK_GIVEN`, satu penilaian per siklus, editable s/d `REPORTED`. Agregat butuh **≥ `accountability.min_responses` (default 3)** respons (ambang anonimitas); respons individual tak pernah diekspos. Tidak memicu transisi siklus. | User checkpoint |
| F4-04 | **M12 Kalibrasi**: sesi kalibrasi fungsional + `CalibrationStats` deterministik teruji (persen kesepakatan, variansi, deviasi absolut, Fleiss' κ). Tidak menyentuh siklus nyata / state machine. | User checkpoint |
| — | Permission M9–M12 disimpan di kode (`RolePermissionMap`), sama seperti Fase 1–3. `memory_limit=512M` ditambahkan ke `phpunit.xml` (arch test butuh > 128M setelah jumlah file domain bertambah). | Fase 4 |
| — | `GeneratePkbRecommendations` membaca `analysis_findings`/`follow_up_*` via `DB::table()` (bukan import model Analysis/FollowUp) agar arah ketergantungan `domain-map.md` terjaga — pola yang sama dengan guard state machine Fase 3. | Fase 4 |

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

### PHASE 3 — Analisis → Pelaporan + AI (M3, M4, M5, M6, M18) ✅ `@provisional` (menunggu review)

| Story | Status | Catatan |
|---|---|---|
| M18 AI abstraction | ✅ | `AiProvider` interface + `MockAiProvider` (default, deterministik) + `OpenAiProvider`/`AnthropicProvider`; `AiServiceProvider` paksa mock tanpa kunci API; `ai_prompt_templates` berversi + `PromptRenderer`; `RunAiGeneration` Job (async); `ai_generations` — setiap keluaran `draft`, `review_status` tak pernah otomatis `accepted`; `GenerateAiDraft` (rate-limited) + `ReviewAiGeneration` (accept/edit/reject) |
| C1 Skoring & analisis (M3) | ✅ | `SchemaDrivenScorer` (deterministik, dari `scoring_config` — weighted-mean-normalized + bands; diuji unit), `AnalysisResult`/`AnalysisFinding`, `PerformAnalysis` (skor + baseline findings), `SaveAnalysisSummary`, `FinalizeAnalysis` (butuh reviewer manusia; **tolak ringkasan `ai_draft` mentah**) → transisi OBSERVATION_DONE→ANALYSIS_DONE; `AnalysisWorkspace` Livewire |
| C2 Draft analisis AI | ✅ | `RequestAnalysisAiDraft` (konteks dari data lolos otorisasi — AI tak akses DB), banner "DRAFT/SARAN AI", tombol Tinjau & Sunting |
| C3 Umpan balik terstruktur (M4) | ✅ | `FeedbackSession`/`FeedbackMessage`/`FeedbackAgreement`, `PostFeedbackMessage` (tipe percakapan), `AcknowledgeFeedback` (guru) → transisi ANALYSIS_DONE→FEEDBACK_GIVEN; `FeedbackRoom` Livewire |
| C4 Saran umpan balik AI | ✅ | pesan bersumber AI ditolak masuk percakapan bila belum `isHumanApproved`; guru tak bisa konfirmasi bila ada saran AI belum ditinjau |
| C5 RTL / Tindak Lanjut (M5) | ✅ | `FollowUpPlan`/`FollowUpItem`/`FollowUpEvidence` (bukti UUID klien — offline R-04), `CreateFollowUpPlan` (jadwalkan reminder H-n dari `policy_settings`) → FEEDBACK_GIVEN→FOLLOW_UP_ACTIVE; `DetectOverdueFollowUps` job harian → tandai `terlambat` → FOLLOW_UP_ACTIVE↔FOLLOW_UP_OVERDUE + eskalasi notifikasi supervisor; `SubmitFollowUpEvidence`/`UpdateFollowUpItem`; `FollowUpTracker` Livewire |
| C6 Pelaporan siklus (M6) | ✅ | `Report`/`ReportSnapshot`, `CompileCycleReport` (materialisasi snapshot 6 tahap; tolak bila RTL terbuka tanpa catatan override) → FOLLOW_UP→REPORTED; `CycleReport` Livewire (print-to-PDF) |
| C7 Agregat (M6) | ✅ | `BuildAggregateReport` (per dinas/sekolah/wilayah/jenjang, tanpa data individual guru, anomali label-saja), `AggregateDashboard` Livewire |
| C8 Arsip | ✅ | `ArchiveReportedCycles` job mingguan → REPORTED→ARCHIVED (data tak dihapus) |
| State machine Fase 3 | ✅ | Guard transisi 2→7 diaktifkan via query tabel (bukan import domain lanjut) — defense-in-depth; Action tiap domain tetap menegakkan prasyarat lengkap |
| API `/api/v1` | ✅ | `GET analysis/draft`, `POST analysis`, `POST feedback`, `PATCH feedback/ack`, `POST follow-up`, `PATCH follow-up/{item}`, `PATCH follow-up/{item}/evidence` (`ability:follow-up:evidence`), `GET reports/cycle/{id}`, `GET reports/aggregate`, `POST ai/generations/{id}/review` |
| Seed | ✅ | 8 siklus melintasi seluruh status (Draf → FollowUpOverdue); 4 template prompt AI; RTL terlambat + reminder demo |

**Tes:** 128 pass / 302 assertions (+13: skoring deterministik, alur pasca-observasi end-to-end, **AI human-in-the-loop** (tak pernah auto-approve, rate-limit, usableText null sampai ditinjau), eskalasi + pemulihan RTL, kompilasi laporan). `composer ci` hijau.
**Browser-verified:** analysis workspace (skor 76% "Baik", section scores, baseline findings, toast), request AI draft.

**Catatan:**
- `QUEUE_CONNECTION=sync` di `.env` dev (AI/notifikasi/reminder langsung); produksi pakai `database` + worker (`.env.example`).
- Ekspor laporan MVP = print-to-PDF browser; PDF/XLSX server-side ditunda.
- Transisi Fase 3 di state machine memakai `DB::table()` query, bukan model domain M3–M6, agar tak melanggar arah dependensi domain-map.

### PHASE 4 — Pengembangan Profesional & Akuntabilitas (M7, M9, M10, M11, M12) ✅ (menunggu review)

| Story | Status | Catatan |
|---|---|---|
| D1 M7 Program Tahunan | ✅ | `annual_programs` + `program_targets`; `SaveAnnualProgram`/`SyncProgramTargets`/`GenerateProgramCycles` (→ `CreateCycle` DRAFT, set `program_id`)/`SetProgramStatus`; `AnnualProgramPolicy` (owner supervisor); Livewire `ProgramIndex`/`ProgramEditor`; API `/programs*`. State machine tak berubah (F4-01). |
| D2 M9 Katalog PKB | ✅ `@provisional` | `pkb_catalog_items` (admin dinas/sistem kelola; global/per-dinas) + `pkb_recommendations`; `SavePkbCatalogItem`/`SetPkbCatalogItemStatus`; `GeneratePkbRecommendations` (deterministik via `PkbMatcher`, tanda `rtl_berulang` bila pola berulang lintas siklus guru); `RespondPkbRecommendation` (guru pilih/tolak/selesai); Livewire `PkbCatalogIndex`/`CyclePkb`. |
| D3 M10 Perpustakaan Praktik Baik | ✅ `@provisional` | `best_practices`; `NominateBestPractice` (skor ≥ policy, siklus REPORTED/ARCHIVED) → `RespondBestPracticeConsent` (guru) → `CurateBestPractice` (admin dinas) → `WithdrawBestPractice`; notifikasi tiap tahap; Livewire `BestPracticeLibrary` + panel di `CyclePkb`. |
| D4 M11 Akuntabilitas 360° | ✅ `@provisional` | `supervisor_evaluations`; `SupervisionProcessSurvey` (5 dimensi Likert 1–4); `SubmitSupervisorEvaluation` (buka `FEEDBACK_GIVEN`..`FOLLOW_UP_OVERDUE`, upsert, tak memicu transisi); `AccountabilityAggregator` (ambang `accountability.min_responses`); Livewire `SupervisorEvaluationForm`/`AccountabilityDashboard`. |
| D5 M12 Kalibrasi Antar-Penilai | ✅ `@provisional` | `calibration_sessions`/`calibration_participants`/`calibration_scores`; `CreateCalibrationSession`/`AddCalibrationParticipant`/`SubmitCalibrationScores`/`CloseCalibrationSession`; `CalibrationStats` (persen kesepakatan, variansi, deviasi absolut, variansi skor total, Fleiss' κ — deterministik, unit-tested); Livewire `CalibrationIndex`/`CalibrationShow`. |
| RBAC | ✅ | 11 permission baru di `Permission` enum + `RolePermissionMap` + 19 baris matriks `rbac.md` diuji parametrik. |
| API `/api/v1` | ✅ | `AnnualProgramController`, `ProfessionalDevController`, `AccountabilityController` — shell tipis atas Action (Spec §8 tidak merinci endpoint modul ini; ditambahkan additive, R-03). |
| Arch guardrails | ✅ | Domain tahap siklus tak boleh `use` `Program`/`ProfessionalDev`/`Accountability`; layer tsb tak boleh menyentuh `CycleStateMachine`/`CycleStatusTransition`; `Ai` tak menyentuh domain Fase 4. |
| Seed | ✅ | 4 item katalog PKB global, 1 program tahunan (3 target → 3 siklus DRAFT), 3 penilaian 360°, 1 sesi kalibrasi selesai (3 penilai, snapshot statistik). |

**Tes:** 192 pass / 454 assertions (+61: `CalibrationStats`, `PkbMatcher`, program generate/idempotensi/scoping, rekomendasi PKB dari analisis + pola berulang, praktik baik nominate→consent→curate + gate skor/PDP/lintas-dinas, 360° timing + ambang anonimitas + tak memicu transisi, kalibrasi end-to-end + guard, render layar Fase 4, arch). `composer ci` hijau (Pint + PHPStan 8 + Pest).

**Catatan:**
- M9–M12 `@provisional` — skema additive-only setelah SLR Gate 6/7 (docblock `@provisional` di model & migrasi).
- API Fase 4 belum diuji feature-level (write path lewat Livewire yang diuji); ditandai known limitation.
- M7 tanpa delegasi pengawas (F4-01) — bila dibutuhkan, `program_assignments` = penambahan additive.

### PHASE 5 — Kesiapan Evaluasi Ahli (DSR Artikel 3) ✅ (menunggu review)

| Story | Status | Catatan |
|---|---|---|
| E1 Paket demo + skenario | ✅ | `docs/demo-script.md` — 6 alur end-to-end + akun demo + perintah terjadwal. Seeder diperluas: 1 panel evaluasi selesai (4 ahli, 3 penilaian, snapshot statistik) di atas data Fase 1–4. |
| E2 Instrumen expert judgment + usability (F5-01) | ✅ | Domain `Evaluation` + peran `ahli`. `ExpertJudgmentInstrument` (8 aspek, relevansi CVR + kualitas Aiken 1–5), `UsabilityQuestionnaire` (SUS 10 butir). `ExpertJudgmentStats` — CVR/CVI (Lawshe + tabel nilai kritis), Aiken's V, SUS + interpretasi, ringkasan per rumpun. Deterministik, unit-tested. `docs/expert-judgment.md`. |
| E2 alur & UI | ✅ | `CreateEvaluationPanel`/`AssignExpertToPanel`/`SubmitExpertReview`/`CloseEvaluationPanel`; `EvaluationPanelPolicy`; Livewire `PanelIndex`/`PanelShow`/`ExpertReviewForm`; route `/evaluation*`; nav + dasbor peran `ahli`. |
| E3 Dokumentasi DSR | ✅ | `docs/dsr-artefak.md` — enam aktivitas Peffers dkk. (2007): problem identification (evidence map Artikel 1) → objectives (7 prinsip non-negosiasi) → design (ADR + iterasi versi lama→v2) → demonstration → evaluation → communication. |
| E4 Technical evaluation (F5-02) | ✅ | `docs/technical-evaluation.md` — konsolidasi tinjauan keamanan + status risk register T-01..T-13, inventaris tes per kategori (pengganti line coverage), hasil uji performa, prosedur uji beban `wrk`. `tests/Feature/Performance/` (3 tes, di `composer ci`). |
| RBAC | ✅ | Peran `Role::Ahli` + 2 permission (`ManageEvaluationPanel`, `SubmitExpertReview`) + 7 baris matriks `rbac.md`. |
| Arch guardrails | ✅ | Domain `Evaluation` terisolasi dari domain siklus & `Ai`; helper statistik murni (tanpa DB). |

**Tes:** 214 pass / 522 assertions (+21: `ExpertJudgmentStats` (CVR/CVI/Aiken/SUS, determinisme, N<5), alur panel evaluasi (create→assign→submit→close, tolak tak lengkap, gate non-ahli/non-peneliti, tak bisa tutup tanpa penilaian), render layar Fase 5, performa dasbor & agregat pada 200 siklus). `composer ci` hijau (Pint + PHPStan 8 + Pest).

**Catatan:**
- Semua domain sistem selesai. Fase berikutnya (bila ada) = uji lapangan / iterasi berbasis hasil evaluasi ahli, bukan modul baru.
- API untuk domain `Evaluation` tidak dibuat (bukan bagian Spec §8; alur cukup lewat Livewire).
- Instrumen expert judgment belum diuji keterbacaan pada panel nyata — redaksi aspek boleh direvisi sebelum panel dijalankan.

### Pasca-Fase 5 — Ekspor laporan server-side (M6) ✅ — commit `d71cd84`

Menggantikan *known limitation* "ekspor = print-to-PDF browser".

| Item | Catatan |
|---|---|
| Tabel `report_exports` | Beberapa berkas per `report` (pdf/xlsx/csv); status `antre`→`diproses`→`siap`/`gagal`. |
| Job `GenerateReportExport` | Idempoten; render → simpan ke disk privat `local` (`storage/app/private/reports/…`). QUEUE sync di dev → inline. |
| Library | `dompdf/dompdf` (PDF, `isRemoteEnabled=false`) + `openspout/openspout` (XLSX, streaming) + `fputcsv` native (CSV). Semua **pure PHP** — tanpa headless browser (sesuai semangat 3T). |
| Actions | `CompileAggregateReport` (materialisasi `Report` scope=dinas dari `BuildAggregateReport`), `RequestReportExport` (authz via `ReportAccess`, dispatch job). Laporan siklus = PDF saja; agregat = pdf/xlsx/csv. |
| Akses | `ReportAccess` (dipakai bersama Action + `ReportExportPolicy`): supervisor/guru siklus + admin dinas dapat mengunduh; hanya supervisor/admin dinas dapat *meminta* ekspor (guru ➖, sesuai `rbac.md`). Unduh lewat route web `reports.exports.download` + Policy. |
| UI | Tombol "Unduh PDF" di `CycleReport`; tombol PDF/XLSX/CSV di `AggregateDashboard`; daftar berkas dengan `wire:poll` status. |
| Tes | +10 (unit `AggregateReportTable`; feature: PDF `%PDF-`, XLSX `PK`, CSV, gate guru/supervisor-lain, unduh pihak terkait vs luar, endpoint API 202). Arch: library render hanya di `Reporting\Rendering`; `AggregateReportTable` murni. **224 tes**. |
| Skema | Additive (1 tabel). `composer.json` +2 dependency. |

### (tidak ada PHASE 6 terencana)

