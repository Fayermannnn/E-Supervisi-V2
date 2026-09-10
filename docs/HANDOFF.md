# HANDOFF — E-Supervisi Klinis Pendidikan v2.0

> Dokumen untuk **melanjutkan project di chat baru**. Salin bagian "PROMPT UNTUK CHAT BARU" di bawah sebagai pesan pertama, lampiran ini + `CLAUDE.md` + `docs/` sudah cukup sebagai konteks.
> Terakhir diperbarui: 2026-09-10 (setelah Fase 5 selesai — MVP lengkap).

---

## 1. Ringkasan status

| Fase | Fokus | Modul | Status |
|---|---|---|---|
| 0 | Discovery & architecture baseline | — | ✅ Selesai (10 artefak di `docs/`, checkpoint R-01..R-04 dijawab) |
| 1 | Fondasi: identitas, RBAC, organisasi, audit, notifikasi | M13–M17 | ✅ Selesai, tag `phase1-complete` |
| 2 | Inti siklus: Perencanaan → Observasi (+ offline PWA, API Sanctum) | M1, M2, M8 | ✅ Selesai, tag `phase2-complete` |
| 3 | Analisis → Umpan Balik → RTL → Pelaporan (+ AI human-in-the-loop) | M3, M4, M5, M6, M18 | ✅ Selesai, tag `phase3-complete`, **`@provisional`** |
| 4 | Pengembangan profesional & akuntabilitas | M7, M9, M10, M11, M12 | ✅ Selesai, tag `phase4-complete` (M9–M12 `@provisional`) |
| 5 | Kesiapan evaluasi ahli (DSR Artikel 3) | — | ✅ Selesai, tag `phase5-complete` |

**Gate hijau saat ini:** `composer ci` = Pint (strict types) clean + PHPStan level 8 "No errors" + **224 Pest tests / 585 assertions pass**.

**Pasca-Fase 5** (commit `d71cd84`, di atas tag `phase5-complete`): ekspor laporan server-side — `dompdf/dompdf` (PDF) + `openspout/openspout` (XLSX), keduanya pure-PHP (aman untuk 3T, tanpa headless browser). `report_exports` + job `GenerateReportExport`. **226 tes hijau.**

**MVP LENGKAP — semua domain terbangun.** Fase 5 menambahkan modul **Evaluasi Ahli** in-app (domain `Evaluation` + peran `ahli`): panel ahli (≥ 2 rumpun) menilai artefak → sistem menghitung **CVR/CVI** (Lawshe), **Aiken's V**, **SUS** (`ExpertJudgmentStats`, deterministik + unit-tested). Dokumen baru: `docs/dsr-artefak.md` (DSR Peffers dkk. 2007), `docs/expert-judgment.md`, `docs/technical-evaluation.md`, `docs/demo-script.md`. `tests/Feature/Performance/` masuk `composer ci`. Keputusan checkpoint: `DECISIONS.md` F5-01, F5-02.

**Fase 4:** program tahunan (M7), katalog PKB + rekomendasi deterministik (M9), praktik baik + consent guru + kurasi dinas (M10), akuntabilitas 360° (M11), kalibrasi antar-penilai (M12). Domain: `Program`, `ProfessionalDev`, `Accountability`. Checkpoint F4-01..F4-04.

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

`app/Domain/`: `Identity`, `Organization`, `Supervision`, `Planning`, `Observation`, `Analysis`, `Feedback`, `FollowUp`, `Reporting`, `Instruments`, `Ai`, `Audit`, `Notification`, `Administration`, `Support`, `Program` (M7), `ProfessionalDev` (M9/M10), `Accountability` (M11/M12), `Evaluation` (Fase 5 — panel evaluasi ahli, DSR Artikel 3).

**Semua domain selesai.** Tidak ada modul baru yang direncanakan. Pekerjaan lanjutan (bila ada) = uji lapangan, iterasi berdasarkan hasil evaluasi ahli, penguncian modul provisional setelah SLR Gate 6/7, atau hardening pra-go-live (lihat `docs/technical-evaluation.md` §5).

---

## 5. Berikutnya — tidak ada fase modul terencana

**MVP Fase 0–5 selesai.** Semua domain terbangun & teruji. Pasca-Fase 5 sudah
dikerjakan: **ekspor laporan server-side** (PDF siklus + PDF/XLSX/CSV agregat,
job `GenerateReportExport`, disk privat) — commit **`d71cd84`**
`feat(reporting): server-side report export (PDF / XLSX / CSV)`.

Kandidat pekerjaan lanjutan (bukan urutan wajib; masing-masing butuh trigger +
checkpoint sendiri):

- **Penguncian modul provisional** setelah SLR Gate 6/7 (M3–M6, M9–M12, M18) —
  revisi skema/prompt bersifat additive; perbarui `@provisional` → final.
- **Jalankan panel evaluasi ahli nyata** lewat `/evaluation`; masukkan hasil
  CVR/CVI, Aiken's V, SUS ke manuskrip Artikel 3 (`docs/dsr-artefak.md` §5).
- **Hardening pra-go-live**: review hukum PDP + dokumen basis pemrosesan,
  secure headers/CSP/HSTS, `tests/Browser` (Pest v4) untuk alur luring→online,
  uji beban lapangan (`docs/technical-evaluation.md` §4–5).
- **Modul JS outbox khusus bukti RTL** (saat ini online via Livewire).

**Proses bila melanjutkan (master prompt §20, §25):** DISCOVER → checkpoint bila
ambiguity → PLAN → implementasi → update docs → `composer ci` hijau → commit
`type(domain): ringkas` (akhiri `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`)
→ tag → laporan 7-bagian.

---

## 6. Utang teknis / batasan diketahui

- **M3–M6, M9–M12, M18 `@provisional`** — skema & prompt direvisi setelah SLR Gate 6/7; hanya additive setelahnya.
- Ekspor laporan **server-side** sudah ada: PDF laporan siklus (dompdf), PDF/XLSX/CSV laporan agregat (dompdf + OpenSpout), via job `GenerateReportExport` ke disk privat. Tombol "Cetak" (window.print) tetap ada sebagai pelengkap.
- `QUEUE_CONNECTION=sync` di dev — ekspor laporan berjalan inline; **produksi butuh `queue:work`** untuk ekspor + AI + notifikasi.
- Bukti RTL offline: endpoint idempoten ada, tapi **belum ada modul JS outbox khusus RTL**.
- Belum ada `tests/Browser` (Pest v4 browser) otomatis untuk alur luring→online.
- FormatB instrument = template placeholder, ditandai belum tervalidasi (`FormatBTemplate`).
- **API Fase 4** (`/programs`, `/pkb/*`, `/best-practices/*`, `/calibration/*`, `/supervisor-evaluation`) belum diuji feature-level — write path terverifikasi lewat Livewire. Domain `Evaluation` (Fase 5) tidak punya API (bukan Spec §8).
- **M7 tanpa delegasi pengawas** (F4-01) — `program_assignments` additive bila dibutuhkan.
- **Instrumen expert judgment (aspek)** belum diuji keterbacaan pada panel nyata — redaksi `ExpertJudgmentInstrument::aspects()` boleh direvisi sebelum panel dijalankan.
- Line coverage tidak dilaporkan (tanpa Xdebug/PCOV) — inventaris tes di `docs/technical-evaluation.md`.
- `phpunit.xml` menyetel `memory_limit=512M` (arch test butuh > 128M).

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
- **F5-01:** Instrumen expert judgment = **modul in-app** (domain `Evaluation` + peran `ahli`), bukan lembar luring.
- **F5-02:** Uji beban ringan = assertion performa Pest (dasbor/agregat pada ~200 siklus) + prosedur `wrk` terdokumentasi.

---

## PROMPT UNTUK CHAT BARU

```
Lanjutkan pengelolaan E-SUPERVISI KLINIS PENDIDIKAN v2.0 (Laravel 13 modular monolith).

Konteks: baca CLAUDE.md, README.md, docs/HANDOFF.md, docs/DECISIONS.md, docs/dsr-artefak.md,
dan docs/ lainnya. Source of truth: Spesifikasi_E-Supervisi_v2_Evidence-Informed.docx.

Status: Fase 0–5 SELESAI (tag phase1..5-complete). MVP LENGKAP — semua domain terbangun.
composer ci hijau (Pint + PHPStan 8 + 214 Pest tests). Modul M3–M6, M9–M12, M18 = @provisional.
Tidak ada fase modul baru terencana (lihat HANDOFF §5 untuk kandidat pekerjaan lanjutan).

Aturan wajib ada di CLAUDE.md — patuhi semua (state machine satu jalur, RBAC default-deny,
AI human-in-the-loop tanpa akses DB & tanpa transisi status, audit append-only, migrasi
non-destruktif, DoD per modul, RULE 10 checkpoint untuk ambiguity arsitektur).
Modul provisional: perubahan skema/prompt HANYA additive.

Jebakan lingkungan: working dir "/Users/firmansyah/CLAUDE CODE" (ada spasi, selalu cd dulu);
JANGAN `| head` (alias rusak); test DB PostgreSQL esupervisi_test; QUEUE sync di dev.

TUGAS: <sebutkan pekerjaan yang diinginkan — mis. penguncian modul provisional pasca-SLR,
menjalankan panel evaluasi ahli nyata, hardening pra-go-live, ekspor laporan server-side,
tests/Browser, atau perbaikan spesifik>.

Mulai dari DISCOVER: baca bagian docs terkait, konfirmasi scope, berhenti di checkpoint
bila ada ambiguity arsitektur. Akhiri dengan composer ci hijau + commit + (bila fase) tag
+ laporan 7-bagian.
```
