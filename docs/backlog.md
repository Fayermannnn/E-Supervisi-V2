# Product Backlog + Acceptance Criteria

Format: `[ID] Judul` — Modul — Fase — kriteria terima (Given/When/Then ringkas).
DoD global (master prompt §22): migration + model + FormRequest + Policy + logic + Livewire UI + error handling + audit (bila relevan) + notifikasi (bila relevan) + test (unit/feature/security) + responsif + dokumentasi.

---

## EPIC A — Fondasi (Fase 1) · Modul M13, M14, M15, M16, M17

### A1 — Skeleton proyek & tooling
- Laravel terbaru, PostgreSQL, Pest, Tailwind, Livewire, Larastan (level max), Pint, Workbox.
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

Backlog ringkas — diperinci saat Fase 4:
- **D1 M7** Program tahunan + delegasi pengawas + generate siklus massal.
- **D2 M9** Katalog PKB + rekomendasi dari hasil analisis/RTL (AI opsional).
- **D3 M10** Perpustakaan praktik baik (kurasi, tag, dari siklus berkinerja baik).
- **D4 M11** Akuntabilitas supervisor 360° (guru menilai proses supervisi, bukan performa mengajarnya).
- **D5 M12** Kalibrasi antar-penilai (hanya bila multi-supervisor; dukung reliabilitas Artikel 2).

---

## EPIC E — Kesiapan Evaluasi (Fase 5)

- **E1** Paket demo + skenario end-to-end + data seed realistis.
- **E2** Instrumen expert judgment (CVR/Aiken's V) + kuesioner usability.
- **E3** Dokumentasi DSR: problem identification → design → demonstration → evaluation.
- **E4** Technical evaluation: audit keamanan, uji beban ringan, laporan cakupan test.
