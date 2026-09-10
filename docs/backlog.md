# Product Backlog + Acceptance Criteria

Format: `[ID] Judul` — Modul — Fase — kriteria terima (Given/When/Then ringkas).
DoD global (master prompt §22): migration + model + FormRequest + Policy + logic + Livewire UI + error handling + audit (bila relevan) + notifikasi (bila relevan) + test (unit/feature/security) + responsif + dokumentasi.

---

## EPIC A — Fondasi (Fase 1) · Modul M13, M14, M15, M16, M17

### A1 — Skeleton proyek & tooling
- Laravel terbaru, PostgreSQL, Pest, Tailwind, Livewire, Larastan (level 8, target naik bertahap), Pint, Workbox.
- **Terima:** `composer test` hijau; `pint --test` bersih; `.env.example` lengkap; CI lokal (script) menjalankan lint+stan+test; `README` & `docs/` ter-commit.

### A2 — Autentikasi (M13)
- Login/logout, lupa password, verifikasi email, throttle login.
- **Terima:** login gagal 5× → lockout; sesi aman (`SESSION_SECURE_COOKIE`, `SameSite=Lax`); password `argon2id`; feature test login/logout/reset; security test brute-force ditolak.

### A3 — RBAC & Policy foundation (M13)
- Enum `Role`, `Permission`; `roles`, `permissions`, pivot; middleware + `Gate`; trait `HasRoles`.
- **Terima:** `rbac.md` matriks terwujud sebagai test parametrik (setiap sel ✅/➖ diverifikasi); default deny; `admin_sistem` tak bisa self-assign tanpa audit.

### A4 — Struktur organisasi (M15)
- CRUD `dinas`, `sekolah`; import CSV sekolah (opsional).
- **Terima:** sekolah wajib `dinas_id`; NPSN unik; hanya `admin_sistem` full, `admin_dinas` terbatas dinasnya; audit tercatat.

### A5 — Manajemen pengguna (M13)
- CRUD user, assign role, assign sekolah, aktif/nonaktif, reset password.
- **Terima:** email unik; nonaktif → tak bisa login; perubahan role → audit `role.assigned/revoked`; `admin_dinas` hanya user di dinasnya.

### A6 — Penugasan supervisor–guru (M13/M15)
- `supervisor_assignments` CRUD, periode, `supervisor_type`.
- **Terima:** tak boleh assign guru lintas dinas untuk kepala_sekolah; overlap periode ditolak; jadi basis scoping siklus.

### A7 — Audit log (M16)
- Tabel append-only, observer global, viewer read-only.
- **Terima:** tak ada route update/delete; tiap create/update/delete entitas domain → 1 baris; `old/new` values; viewer di-scope; `explain` test memastikan index terpakai.

### A8 — Notifikasi foundation (M14)
- Channel `database` + `mail`; `reminder_schedules`; queue worker; preferensi notifikasi user.
- **Terima:** notifikasi tersimpan & tampil di bell UI; job terjadwal terkirim; user bisa mute kanal; test job.

### A9 — Konfigurasi & kebijakan (M15)
- `policy_settings` key-value per dinas + global; UI admin.
- **Terima:** override dinas menimpa global; perubahan → audit; cache dengan invalidasi.

### A10 — Bantuan & dukungan (M17)
- `help_articles` (markdown), FAQ, form `support_tickets`.
- **Terima:** artikel publik utk user login; tiket → notifikasi admin; kategori.

### A11 — Shell layout & design system
- Layout autentikasi, navbar per peran, komponen Blade dasar, dark/light, halaman error.
- **Terima:** nav sesuai peran; komponen terdokumentasi di `/help/dev` atau storybook sederhana; aksesibilitas dasar lulus axe.

---

## EPIC B — Inti Siklus: Perencanaan → Observasi (Fase 2) · M1, M2, M8

### B1 — Bank instrumen (M8)
- CRUD `instruments` + `instrument_versions` (`schema_json`, `scoring_config`), publish/arsip, editor item (seksi→item→tipe).
- **Terima:** versi ter-publish immutable; instrumen global read-only bagi dinas; validasi `schema_json` (JSON Schema); seed **Format B placeholder** bertanda contoh.

### B2 — Buat siklus (M1) — `POST /cycles`
- Pilih guru binaan, tahun ajaran, semester, judul; status `DRAFT`.
- **Terima:** hanya guru dari `supervisor_assignments` aktif; `dinas_id`/`sekolah_id` terisi dari guru; audit `cycle.created`; API + Livewire memakai Action sama.

### B3 — Perencanaan & kesepakatan (M1) — `PATCH /cycles/{id}/schedule`
- `planning_agreement`: fokus, tujuan, instrumen+versi, tipe observasi, jadwal, kelas/mapel; tombol kesepakatan guru & supervisor; transisi `DRAFT→SCHEDULED`.
- **Terima:** transisi ditolak jika kesepakatan belum lengkap (guard `state-machine.md`); notifikasi ke guru saat `SCHEDULED`; guru bisa lihat tapi tak ubah fokus.

### B4 — Refleksi guru pra-observasi (M1) — `POST /cycles/{id}/reflections`
- Guru mengisi refleksi sebelum jadwal.
- **Terima:** hanya guru pemilik; tersimpan dengan `submitted_at`; tampil bagi supervisor; wajib/opsional dikontrol `policy_settings`.

### B5 — Konsol observasi online (M2)
- Livewire: render instrumen dari `schema_json`, isi item, catatan skrip, autosave (debounce), progres item `required`.
- **Terima:** autosave tiap perubahan (optimistic lock `version`); reload memulihkan state; item `required` divalidasi saat finalize.

### B6 — Observasi offline + sync (M2) — **kritis** (ADR-006)
- Modul JS: IndexedDB store, outbox, Service Worker, retry backoff, deteksi konflik, `<x-sync-indicator>`.
- Endpoint `GET /sync/bootstrap`, `POST /sync/observations`, `POST /sync/observations/{id}/resolve`.
- **Terima:**
  - *Given* jaringan mati *when* observer mengisi & menutup tab *then* data ada di IndexedDB;
  - *when* online kembali *then* outbox ter-flush, server menyimpan (idempoten pada retry ganda — tidak ada duplikat baris `observation_responses`);
  - *Given* server `version` berubah *when* push *then* HTTP 409 + UI resolusi konflik;
  - media besar: metadata tersimpan, file antre unggah, tidak memblok sync teks;
  - test: Pest feature (endpoint idempoten/konflik) + browser test (Dusk/Playwright) skenario luring→online.

### B7 — Finalisasi observasi (M2) — `POST /observations/{id}/finalize`
- Kunci observasi, transisi `SCHEDULED→OBSERVATION_DONE`, snapshot instrumen.
- **Terima:** semua item `required` terisi; observasi jadi read-only; `cycle_status_transitions` + audit; notifikasi guru "hasil observasi tersedia" (sesuai kebijakan tampil).

### B8 — Unggah media observasi (M2) — `POST /observations/{id}/media`
- Upload aman (validasi mime/size, disk privat, nama acak), chunked opsional, otorisasi akses file.
- **Terima:** file di luar web root; akses via signed route + Policy; virus/mime check; guru hanya media siklusnya.

### B9 — Dasbor supervisor (S1)
- Siklus per status, RTL mendekati/terlambat (placeholder sampai Fase 3), progres, quick actions.
- **Terima:** hanya siklus binaan; angka → drilldown; performa < 300ms untuk 200 siklus (query + index).

### B10 — Dasbor & tampilan guru
- Jadwal, status siklus, akses refleksi/hasil.
- **Terima:** hanya siklusnya; hasil observasi hanya setelah final.

---

## EPIC C — Pasca-observasi (Fase 3) · M3, M4, M5, M6, M18 — `@provisional`

> ⛔ Spec §13: jangan kunci DB/API final untuk modul ini sampai Gate 6/7 SLR. Bangun dengan business rule **minimal & configurable**; tandai `@provisional`. Lihat `risk-register.md` R-01 — **butuh keputusan checkpoint**.

### C1 — Skoring & analisis manual (M3)
- Hitung skor dari `scoring_config` + `observation_responses`; `analysis_result` + `analysis_findings`; review → `finalizeAnalysis` (manusia).
- **Terima:** skor deterministik & ada test unit; finalize butuh reviewer manusia; transisi `OBSERVATION_DONE→ANALYSIS_DONE`; AI tak bisa memicu.

### C2 — Draft analisis AI (M18/M3) — `GET /cycles/{id}/analysis/draft`
- Job → `AiProvider->summarizeObservation(context)` → `ai_generations` status `draft`; banner UI.
- **Terima:** MockAiProvider default & deterministik; output tak pernah tersimpan sbagai `analysis_result` final tanpa `review_status ∈ {accepted, edited}`; prompt version tercatat.

### C3 — Ruang umpan balik terstruktur (M4)
- `feedback_session` + `feedback_messages` (tipe percakapan) + `feedback_agreements`; konfirmasi guru `PATCH /feedback/ack`; transisi `ANALYSIS_DONE→FEEDBACK_GIVEN`.
- **Terima:** guru bisa menanggapi & menyepakati; transisi butuh `status_konfirmasi_guru = dikonfirmasi`; timestamp & audit.

### C4 — Saran umpan balik AI (M18/M4)
- Job → draft saran; supervisor edit sebelum kirim.
- **Terima:** pengiriman final ditolak bila konten = output AI mentah belum ditinjau.

### C5 — RTL / Tindak Lanjut (M5) — **prioritas tinggi**
- `follow_up_plan` + `follow_up_items` (tujuan, indikator keberhasilan, tenggat) + checklist + `follow_up_evidence`.
- Endpoint `POST /cycles/{id}/follow-up`, `PATCH /follow-up/{id}`, `PATCH /follow-up/{id}/evidence`.
- Job harian: deteksi `tenggat < today` → status `terlambat` → transisi siklus `FOLLOW_UP_OVERDUE` → eskalasi notifikasi supervisor.
- **Terima:**
  - guru unggah bukti (offline-capable, endpoint `/sync/follow-up-evidence`);
  - reminder H-3/H-1 terjadwal saat RTL aktif;
  - overdue → eskalasi + kartu merah di dasbor supervisor;
  - bukti masuk → status kembali `berjalan`/`selesai`;
  - test unit deteksi keterlambatan (batas tanggal), feature eskalasi, security (guru lain tak bisa unggah).

### C6 — Pelaporan per siklus (M6) — `GET /reports/cycle/{id}`
- Rangkuman 6 tahap + skor + RTL + status; ekspor PDF (fase awal), XLSX/CSV bertahap; transisi `→REPORTED`.
- **Terima:** hanya siklus lengkap (atau override dengan catatan); `report_snapshot` materialisasi; ekspor via job; audit.

### C7 — Dasbor & laporan agregat (M6) — `GET /reports/aggregate` (S7)
- Agregat per dinas/sekolah/wilayah/periode; filter; ekspor; anomali AI (label saja).
- **Terima:** `admin_dinas` hanya dinasnya; angka actionable; tidak membocorkan data individual guru di luar kebijakan; performa dengan `report_snapshots`.

### C8 — Arsip siklus
- Job retensi: `REPORTED→ARCHIVED` pada penutupan periode; data tak dihapus.
- **Terima:** arsip read-only; tetap muncul di analitik/PKB; audit.

---

## EPIC D — Pengembangan Profesional & Kualitas (Fase 4) · M7, M9–M12

Keputusan checkpoint: `DECISIONS.md` F4-01..F4-04. M9–M12 = `@provisional` (additive-only setelah SLR Gate 6/7).

### D1 — Program Supervisi Tahunan (M7) — Confirmed
- `annual_programs` dimiliki **supervisor** (F4-01) + `program_targets` (guru binaan + fokus + rencana tanggal).
- Actions: `SaveAnnualProgram`, `SyncProgramTargets` (validasi binaan aktif), `GenerateProgramCycles` (→ `CreateCycle` per target, siklus **DRAFT**, `program_id` di-set, idempoten), `SetProgramStatus`.
- **Terima:** hanya guru binaan aktif jadi target; generate kedua = no-op untuk target yang sudah punya siklus; supervisor lain tak bisa menyunting; state machine tak berubah; Livewire + API pakai Action sama.

### D2 — Katalog PKB (M9) — @provisional
- `pkb_catalog_items` (admin dinas/sistem kelola; `pemilik_dinas_id` null = global) + `pkb_recommendations` (per siklus).
- `GeneratePkbRecommendations`: deterministik (`PkbMatcher`, irisan kata kunci area pengembangan × tag katalog), tandai `rtl_berulang` bila kata kunci muncul di ≥ `professional_dev.pkb_recurrence_threshold` siklus guru. Baca `analysis_findings` via query tabel.
- `RespondPkbRecommendation` (guru: `dipilih`/`ditolak`; lalu `selesai`).
- **Terima:** rekomendasi tak menimpa keputusan guru; hanya item terbit yang berlaku utk dinas; ditolak sebelum analisis final; test unit `PkbMatcher` + feature end-to-end.

### D3 — Perpustakaan Praktik Baik (M10) — @provisional
- `best_practices` (unik per siklus). Alur `menunggu_consent → menunggu_kurasi → terbit/ditolak/ditarik`.
- `NominateBestPractice` (supervisor, siklus REPORTED/ARCHIVED, `score_summary.total ≥ professional_dev.best_practice_min_score`), `RespondBestPracticeConsent` (guru — UU PDP), `CurateBestPractice` (admin dinas), `WithdrawBestPractice`.
- **Terima:** tak terbit tanpa consent guru; skor di bawah ambang ditolak; admin dinas lain tak bisa mengkurasi; notifikasi tiap tahap; opsi anonim.

### D4 — Akuntabilitas Supervisor 360° (M11) — @provisional
- `supervisor_evaluations` (unik per siklus). `SupervisionProcessSurvey` = 5 dimensi Likert 1–4 + komentar.
- `SubmitSupervisorEvaluation` (guru, buka `FEEDBACK_GIVEN`..`FOLLOW_UP_OVERDUE`, upsert, **tidak** memicu transisi).
- `AccountabilityAggregator`: rata-rata per dimensi untuk supervisor (miliknya) & admin dinas (lintas sekolah); disembunyikan bila responden < `accountability.min_responses` (default 3).
- **Terima:** hanya guru siklus yang menilai; agregat < ambang → "data belum cukup"; respons individual tak pernah muncul; test batas ambang + "tak memicu transisi".

### D5 — Kalibrasi Antar-Penilai (M12) — @provisional
- `calibration_sessions` + `calibration_participants` + `calibration_scores`. Dibuat admin dinas/sistem, menaut ke `instrument_version` (+ observasi/artefak).
- `AddCalibrationParticipant`, `SubmitCalibrationScores` (skor item independen, validasi terhadap skema), `CloseCalibrationSession` (≥2 penilai submit → hitung + simpan `stats`).
- `CalibrationStats` (deterministik, unit-tested): persen kesepakatan per item + keseluruhan, variansi, rentang, deviasi absolut rata-rata, variansi skor total, **Fleiss' κ**.
- **Terima:** tak bisa ditutup < 2 penilai; non-peserta tak bisa mengirim skor; supervisor tak bisa membuat sesi; statistik deterministik & diuji.

---

## EPIC E — Kesiapan Evaluasi (Fase 5) ✅

Keputusan checkpoint: `DECISIONS.md` F5-01 (E2 = modul in-app), F5-02 (E4 = assertion performa Pest + prosedur manual).

### E1 — Paket demo + skenario
- `docs/demo-script.md`: 6 alur (siklus penuh, program tahunan, PKB & praktik baik, 360°, kalibrasi, evaluasi ahli), tabel akun demo, perintah terjadwal.
- Seeder (`DatabaseSeeder::seedFase5`): 1 panel evaluasi selesai — 4 ahli (2+2 rumpun), 3 penilaian terkirim, snapshot statistik.
- **Terima:** `migrate:fresh --seed` menghasilkan data di setiap status siklus + seluruh fitur Fase 4–5 dapat diperagakan tanpa kunci API.

### E2 — Instrumen expert judgment + usability (in-app, F5-01) — @provisional pada redaksi aspek
- Domain `Evaluation`: `evaluation_panels`, `panel_experts` (rumpun), `expert_reviews` (jawaban jsonb). Peran `ahli`.
- `ExpertJudgmentInstrument` (8 aspek; relevansi esensial/berguna/tidak_perlu + kualitas 1–5), `UsabilityQuestionnaire` (SUS 10 butir).
- `ExpertJudgmentStats` (deterministik, unit-tested): CVR per aspek + signifikansi (tabel Lawshe), CVI, Aiken's V per aspek + rata-rata, SUS per ahli + rata-rata + interpretasi, ringkasan per rumpun.
- Actions: `CreateEvaluationPanel`, `AssignExpertToPanel` (auto-assign role `ahli`), `SubmitExpertReview` (validasi lengkap; editable selama panel berjalan), `CloseEvaluationPanel` (≥1 penilaian → hitung + snapshot).
- Livewire: `PanelIndex`, `PanelShow` (undang ahli, hasil, tutup), `ExpertReviewForm`.
- **Terima:** review tak lengkap ditolak; non-ahli/non-peneliti ditolak; ahli tak terdaftar tak bisa mengirim; panel tanpa penilaian tak bisa ditutup; statistik deterministik & diuji; `docs/expert-judgment.md` sebagai rujukan isi + rumus.

### E3 — Dokumentasi DSR
- `docs/dsr-artefak.md`: problem identification (evidence map Artikel 1) → objectives (7 prinsip non-negosiasi sbg kriteria evaluasi) → design & development (ADR-001..015, iterasi versi lama→v2, peta 6 fase) → demonstration → evaluation (expert judgment + teknis) → communication.

### E4 — Technical evaluation (F5-02)
- `docs/technical-evaluation.md`: konsolidasi tinjauan keamanan + status `risk-register.md` T-01..T-13; inventaris tes per kategori (pengganti line coverage — tanpa Xdebug/PCOV); hasil uji performa; prosedur uji beban `wrk`.
- `tests/Feature/Performance/AggregateAndDashboardPerformanceTest.php`: dasbor supervisor & `BuildAggregateReport` pada ~200 siklus — batas jumlah query + wall-clock; verifikasi index tersedia.
- **Terima:** tidak ada N+1 pada jalur baca kunci; performa dalam batas; berjalan di `composer ci`.
