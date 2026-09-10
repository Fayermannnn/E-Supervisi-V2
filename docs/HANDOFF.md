# HANDOFF — E-Supervisi Klinis Pendidikan v2.0

> Dokumen untuk **melanjutkan project di chat baru**. Salin bagian "PROMPT UNTUK CHAT BARU" di bawah sebagai pesan pertama, lampiran ini + `CLAUDE.md` + `docs/` sudah cukup sebagai konteks.
> Terakhir diperbarui: 2026-09-10 (setelah Fase 4 selesai).

---

## 1. Ringkasan status

| Fase | Fokus | Modul | Status |
|---|---|---|---|
| 0 | Discovery & architecture baseline | — | ✅ Selesai (10 artefak di `docs/`, checkpoint R-01..R-04 dijawab) |
| 1 | Fondasi: identitas, RBAC, organisasi, audit, notifikasi | M13–M17 | ✅ Selesai, tag `phase1-complete` |
| 2 | Inti siklus: Perencanaan → Observasi (+ offline PWA, API Sanctum) | M1, M2, M8 | ✅ Selesai, tag `phase2-complete` |
| 3 | Analisis → Umpan Balik → RTL → Pelaporan (+ AI human-in-the-loop) | M3, M4, M5, M6, M18 | ✅ Selesai, tag `phase3-complete`, **`@provisional`** |
| 4 | Pengembangan profesional & akuntabilitas | M7, M9, M10, M11, M12 | ✅ Selesai, tag `phase4-complete` (M9–M12 `@provisional`) |
| 5 | Kesiapan evaluasi ahli (DSR Artikel 3) | — | ⏳ **BERIKUTNYA** |

**Gate hijau saat ini:** `composer ci` = Pint (strict types) clean + PHPStan level 8 "No errors" + **192 Pest tests / 454 assertions pass**.

**Fase 4 menambahkan:** program supervisi tahunan (M7 — pemilik supervisor, generate siklus DRAFT massal), katalog PKB + rekomendasi deterministik (M9), perpustakaan praktik baik dengan consent guru + kurasi dinas (M10), akuntabilitas 360° dengan ambang anonimitas (M11), kalibrasi antar-penilai + `CalibrationStats` (M12). Domain baru: `Program`, `ProfessionalDev`, `Accountability`. Keputusan checkpoint: `DECISIONS.md` F4-01..F4-04.

Siklus supervisi sudah **lengkap end-to-end**: Perencanaan → Observasi (offline) → Analisis (skoring deterministik + draf AI) → Umpan Balik terstruktur → Tindak Lanjut (RTL + eskalasi overdue) → Pelaporan (per-siklus + agregat) → Arsip.

---

## 2. Lingkungan (sudah terpasang, jangan setup ulang)

- **Working dir:** `/Users/firmansyah/CLAUDE CODE` (ada spasi — selalu `cd "/Users/firmansyah/CLAUDE CODE"` di awal tiap perintah bash).
- Laravel 13.31 · PHP 8.4.23 (Laravel Herd) · Composer 2.10 · PostgreSQL 16.15 · Node 25.
- DB: `esupervisi` (dev) / `esupervisi_test` (test). User pg: `firmansyah`, tanpa password. `DB_CONNECTION=pgsql`.
- `.env`: `AI_PROVIDER=mock`, `QUEUE_CONNECTION=sync` (dev), `APP_URL=http://e-supervisi.test` (Herd), `SESSION_DRIVER=database`.
- Livewire 3 · Tailwind 4 (`@tailwindcss/vite`) · Pest 5 + PHPUnit 13 · Larastan 8 · Pint (strict preset).

### Perintah
```bash
cd "/Users/firmansyah/CLAUDE CODE"
composer ci                              # GATE sebelum commit: pint --test + phpstan-8 + pest
composer test                            # pest saja
composer stan                            # phpstan level 8
composer lint                            # pint --fix
php artisan migrate:fresh --seed
npm run dev
```

### Login seeder
- Admin Sistem: `admin.sistem@esupervisi.test` / `password`
- Supervisor (kepsek): `kepsek.mahulu.1@esupervisi.test` / `password`
- Seeder membuat 8 penugasan + siklus di setiap status (Draft..FollowUpOverdue).

### Jebakan shell/tooling (PENTING)
- `head` di zsh user **rusak** (ter-alias ke HTTP tool). **Jangan pernah `| head`.** Gunakan `Read`, `sed -n`, `grep`, atau `tail`.
- Larastan `nullsafe.neverNull`: `$x?->y ?? $d` di-flag → tulis `$x !== null ? $x->y : $d`.
- Model butuh `@property` PHPDoc lengkap agar Larastan bisa infer cast/nullable.
- Strict mode (`Model::shouldBeStrict`): Livewire re-hydrate tanpa relasi → pakai `$m->relasi()->first()` / `->sole()`, bukan magic property.
- Postgres: unique index dengan kolom nullable → pakai **partial unique index** `WHERE col IS NULL`.
- Test DB PostgreSQL (bukan SQLite) — paritas jsonb/enum.
- **Jangan** `Date::use(CarbonImmutable)` (bentrok ekspektasi Larastan) — pernah dicoba & di-revert.

---

## 3. Arsitektur & aturan non-negosiasi (dari `CLAUDE.md` + master prompt)

1. **Modular monolith.** Kode domain di `app/Domain/<Domain>/`. Larangan import lintas domain → `docs/domain-map.md` (diuji di `tests/Arch/LayerTest.php`). Model Eloquent di `app/Models` dengan docblock `@domain`.
2. **State machine siklus = satu-satunya jalur ubah status.** `App\Domain\Supervision\StateMachine\CycleStateMachine`. **10 status, tidak ada tambahan** (`App\Support\Enums\CycleStatus`, int enum 0–9). `transition()` = validasi map + cek role + guard + DB transaction (update status + `cycle_status_transitions` append-only + audit + event `CycleTransitioned`). Idempoten no-op pada status sama. Aktor `null` = system, hanya untuk edge yang mengizinkan `system`. Guard Fase 3 pakai `DB::table(...)` (bukan model domain M3–M6) agar arah dependensi terjaga.
3. **RBAC default deny.** `Role` + `Permission` enum di `App\Support\Enums`, `RolePermissionMap` (di kode, bukan DB), Gate per permission di `IdentityServiceProvider`, 8+ Policy. `role_assignments` (user_id, role, dinas_id nullable, assigned_by). `HasRoles::resolvedRoleAssignments()` hindari lazy-load strict-mode. Setiap query siklus lewat global scope.
4. **Audit log append-only.** `audit_logs` tanpa updated_at/deleted_at; model blok update/delete via `booted()`. `App\Domain\Audit\AuditLogger::log()` + trait `Auditable`.
5. **AI (`app/Domain/Ai`)** — RULE 4: AI = assistive, bukan pengambil keputusan.
   - **Tidak akses DB langsung** (diuji arch). Konteks dibangun Action pemanggil dari data yang sudah lolos Policy.
   - Output **selalu `status=draft`**, `review_status` tidak pernah otomatis `accepted`/`edited` — hanya via `ReviewAiGeneration` (accept/edit/reject) oleh manusia dengan `ai.review_draft`.
   - **Tidak memicu transisi status.** `AiGeneration::usableText()` → `null` sampai `isHumanApproved()` (accepted/edited AND reviewer_id set).
   - Abstraction: `Contracts\AiProvider::generate(AiRequest): AiResult`. Impl `MockAiProvider` (default, deterministik), `OpenAiProvider`, `AnthropicProvider`. `AiServiceProvider` paksa `mock` bila tak ada kunci API. Semua panggilan = queued `Jobs\RunAiGeneration`.
   - Gerbang ditegakkan berlapis: `FinalizeAnalysis` tolak `sumber=ai_draft`; `PostFeedbackMessage` tolak pesan AI belum approved; `AcknowledgeFeedback` tolak bila ada saran AI belum ditinjau; state machine blok aktor null di edge bergerbang-manusia.
6. **Migrasi non-destruktif.** Drop kolom/tabel berdata → konfirmasi user eksplisit (RULE 8).
7. **Modul Provisional (M3–M6, M18):** entitas & endpoint `@provisional`. Setelah SLR Gate 6/7 hanya perubahan **additive**. Lihat `docs/risk-register.md`, `docs/DECISIONS.md`.
8. **DoD per modul** (`docs/backlog.md`): migration + model + FormRequest + Policy + logic (Service/Action) + Livewire UI + error handling + audit + notifikasi + test (unit/feature/security) + responsif + dokumentasi. "UI jadi" ≠ "selesai".
9. **RULE 10:** ambiguity yang mempengaruhi arsitektur → **berhenti**, jelaskan opsi via AskUserQuestion, tunggu jawaban.
10. **RULE 3:** setiap fitur wajib ada dasar requirement dari spec (`Spesifikasi_E-Supervisi_v2_Evidence-Informed.docx`).

### Pola implementasi baku
- **Action class dipakai bersama** oleh Livewire component DAN API controller (jangan duplikasi logic).
- API `/api/v1` (Sanctum): `ApiController` base (`use AuthorizesRequests`, envelope `ok()`/`fail()` = `{data,meta}` / `{errors,meta}`). FormRequest di `app/Http/Requests/Api/`. Controller catch `\DomainException|RuntimeException` → 422; `AuthorizationException` dibiarkan bubble → 403.
- Offline: `resources/js/observation-console.js` (IndexedDB `state`+`outbox`, `plain()` = `JSON.parse(JSON.stringify())` untuk strip Alpine proxy, debounced autosave, retry 20s, conflict detection). `public/sw.js` network-first + `/offline` fallback.
- Idempotensi offline: client-generated UUID (`observations`, `follow_up_evidence`) + payload hash → 409 pada konflik.
- UUID v7 PK via trait `HasUuids` (`Str::uuid7()`).
- Scheduled: `esupervisi:dispatch-reminders` (5min), `esupervisi:detect-overdue-followups` (harian 06:15), `esupervisi:archive-cycles` (mingguan).

---

## 4. Peta domain yang sudah ada

`app/Domain/`: `Identity`, `Organization`, `Supervision`, `Planning`, `Observation`, `Analysis`, `Feedback`, `FollowUp`, `Reporting`, `Instruments`, `Ai`, `Audit`, `Notification`, `Administration`, `Support`, `Program` (M7), `ProfessionalDev` (M9/M10), `Accountability` (M11/M12).

Semua domain sudah dibangun. Fase 5 = kesiapan evaluasi ahli (paket demo, instrumen expert judgment, dokumentasi DSR) — bukan modul baru.

---

## 5. Fase 5 — apa yang harus dikerjakan berikutnya

Trigger: user mengetik **"lanjut Fase 5"**. Jangan mulai tanpa itu.

Fase 5 = **Kesiapan Evaluasi Ahli (DSR Artikel 3)** — bukan modul baru. Backlog EPIC E:
- **E1** Paket demo + skenario end-to-end + data seed realistis (sudah cukup lengkap; perlu skrip walkthrough).
- **E2** Instrumen expert judgment (CVR/Aiken's V) + kuesioner usability.
- **E3** Dokumentasi DSR: problem identification → design → demonstration → evaluation.
- **E4** Technical evaluation: audit keamanan, uji beban ringan, laporan cakupan test.

**Prasyarat spec §12:** "Sistem MVP (Fase 1–3) berjalan" ✅ (Fase 4 juga selesai).

**Proses tiap fase (master prompt §20, §25):**
1. DISCOVER → baca bagian spec terkait, cek `docs/backlog.md` + `docs/domain-map.md`.
2. Bila ada ambiguity arsitektur → **checkpoint** (RULE 10) via AskUserQuestion.
3. PLAN → ARCHITECT → DATABASE → BACKEND → FRONTEND → INTEGRATION → TEST → SECURITY REVIEW → UX REVIEW → DOCUMENTATION.
4. Update `docs/DECISIONS.md` + `README.md` + docs modul terkait.
5. `composer ci` hijau → commit `type(domain): ringkas` (akhiri `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`) → tag `phase5-complete`.
6. Tulis **laporan 7-bagian** ke user.

---

## 6. Utang teknis / batasan diketahui

- **M3–M6, M18 dan M9–M12 `@provisional`** — skema & prompt direvisi setelah SLR Gate 6/7; hanya additive setelahnya.
- Ekspor laporan MVP = **print-to-PDF browser** (`window.print()`); PDF/XLSX server-side ditunda.
- `QUEUE_CONNECTION=sync` di dev; `.env.example` tetap `database` + catatan worker untuk produksi.
- Bukti RTL offline: endpoint idempoten ada, tapi **belum ada modul JS outbox khusus RTL**.
- Belum ada `tests/Browser` (Pest v4 browser) otomatis untuk alur Fase 3–4.
- FormatB instrument = template placeholder, ditandai belum tervalidasi (`FormatBTemplate`).
- **API Fase 4** (`/programs`, `/pkb/*`, `/best-practices/*`, `/calibration/*`, `/supervisor-evaluation`) belum diuji feature-level — write path terverifikasi lewat Livewire. Spec §8 tak merinci endpoint modul ini (ditambahkan additive).
- **M7 tanpa delegasi pengawas** (keputusan F4-01) — `program_assignments` bisa ditambahkan additive bila dibutuhkan.
- `phpunit.xml` menyetel `memory_limit=512M` (arch test butuh > 128M setelah jumlah file domain bertambah).

---

## 7. Checkpoint yang sudah dijawab (jangan tanya ulang)

- **R-01:** Lanjut sekarang, tandai modul provisional `@provisional`.
- **R-02:** Multi-dinas via **scoping** (dinas_id + global scope), bukan multi-tenant terpisah.
- **R-03:** API **internal-first** (bukan public API dulu).
- **R-04:** Offline untuk **Observasi + bukti RTL**.
- **F4-01:** M7 program dimiliki **supervisor** (tanpa delegasi); siklus di-generate **DRAFT**; state machine tak berubah.
- **F4-02:** M10 praktik baik: nominasi supervisor → **consent guru** → kurasi Admin Dinas → terbit.
- **F4-03:** M11 360° buka sejak `FEEDBACK_GIVEN`, editable s/d `REPORTED`, agregat butuh ≥ `accountability.min_responses` (3).
- **F4-04:** M12 kalibrasi fungsional + `CalibrationStats` deterministik teruji.

---

## PROMPT UNTUK CHAT BARU

```
Lanjutkan pembangunan E-SUPERVISI KLINIS PENDIDIKAN v2.0 (Laravel 13 modular monolith).

Konteks: baca CLAUDE.md, README.md, docs/HANDOFF.md, docs/DECISIONS.md, dan docs/ lainnya.
Source of truth: Spesifikasi_E-Supervisi_v2_Evidence-Informed.docx.

Status: Fase 0–4 SELESAI (tag phase1..4-complete). composer ci hijau (Pint + PHPStan 8 + 192 Pest tests).
Siklus supervisi lengkap end-to-end + lapisan pengembangan profesional & akuntabilitas.
Modul M3–M6, M18, M9–M12 = @provisional.

Aturan wajib ada di CLAUDE.md — patuhi semua (state machine satu jalur, RBAC default-deny,
AI human-in-the-loop tanpa akses DB & tanpa transisi status, audit append-only, migrasi
non-destruktif, DoD per modul, RULE 10 checkpoint untuk ambiguity arsitektur).

Jebakan lingkungan: working dir "/Users/firmansyah/CLAUDE CODE" (ada spasi, selalu cd dulu);
JANGAN `| head` (alias rusak); test DB PostgreSQL esupervisi_test; QUEUE sync di dev.

TUGAS: kerjakan Fase 5 (Kesiapan Evaluasi Ahli — DSR Artikel 3). Bukan modul baru:
paket demo + skenario, instrumen expert judgment (CVR/Aiken's V), kuesioner usability,
dokumentasi DSR (problem→design→demonstration→evaluation), technical evaluation.

Mulai dari DISCOVER: baca spec §12, §14 + docs/backlog.md EPIC E, konfirmasi scope,
berhenti di checkpoint bila ada ambiguity. Setelah itu PLAN → implementasi bertahap.
Akhiri dengan composer ci hijau, commit, tag phase5-complete, dan laporan 7-bagian.
```
