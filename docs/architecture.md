# Architecture Decision Records (ADR)

Status: **DRAFT for checkpoint review.** Setiap keputusan mencantumkan alasan + konsekuensi.
Sumber kebenaran: `Spesifikasi_E-Supervisi_v2_Evidence-Informed.docx` (selanjutnya "Spec §x").

---

## ADR-001 — Modular monolith, bukan microservices

**Keputusan.** Satu aplikasi Laravel dengan batas domain eksplisit di `app/Domain/*`. Tanpa service terpisah, message broker, atau database per modul.

**Alasan.** Spec §3.2 ("Modular monolith: kompleksitas operasional rendah untuk tim pengembang kecil/individual"). Tim = individu/kecil. DSR memerlukan artefak yang bisa didemonstrasikan & dievaluasi, bukan infrastruktur terdistribusi.

**Konsekuensi.**
- (+) Deploy sederhana, transaksi ACID lintas modul, refactor murah.
- (−) Disiplin batas domain harus dijaga secara konvensi (code review, static analysis), bukan dipaksa jaringan.
- Modul berkomunikasi via **Service/Action calls** dan **domain events**, bukan HTTP internal.

---

## ADR-002 — Struktur direktori domain-oriented

```
app/
├── Domain/
│   ├── Identity/          # M13 — user, role, permission, profil
│   ├── Organization/      # M15 (bagian) — dinas, sekolah, wilayah
│   ├── Supervision/       # siklus + state machine (jantung sistem)
│   ├── Planning/          # M1 — kesepakatan pra-observasi, jadwal, refleksi guru
│   ├── Observation/       # M2 — sesi observasi, pengisian instrumen, media, offline sync
│   ├── Analysis/          # M3 — skoring, pola, draft analisis, review
│   ├── Feedback/          # M4 — sesi umpan balik terstruktur, konfirmasi guru
│   ├── FollowUp/          # M5 — RTL, tenggat, bukti, eskalasi
│   ├── Reporting/         # M6 — laporan per siklus & agregat, ekspor
│   ├── Instruments/       # M8 — bank Format A–E, versioning, item schema
│   ├── Program/           # M7 — program supervisi tahunan, delegasi
│   ├── ProfessionalDev/   # M9 katalog PKB, M10 perpustakaan praktik baik
│   ├── Accountability/    # M11 akuntabilitas supervisor 360°, M12 kalibrasi antar-penilai
│   ├── Notification/      # M14 — notifikasi, pengingat, eskalasi
│   ├── Administration/    # M15 — konfigurasi kebijakan, struktur organisasi
│   ├── Audit/             # M16 — audit log append-only
│   ├── Support/           # M17 — bantuan, FAQ, kanal kendala
│   └── Ai/                # M18 — abstraction layer, provider, prompt versioning
├── Models/                # Eloquent models (shared, dipetakan ke domain via namespace docblock)
├── Livewire/              # komponen UI per domain
├── Policies/
├── Support/               # helper lintas domain (enums, value objects, DTO)
└── Providers/
```

Tiap domain berisi subfolder sesuai kebutuhan: `Actions/`, `Services/`, `Data/` (DTO), `Events/`, `Listeners/`, `Jobs/`, `Enums/`, `Exceptions/`.

**Alasan.** Master prompt §4; Spec §3.2. Nama domain diselaraskan dengan 6 tahap operasional Spec §2.2 agar traceable.

**Konsekuensi.** Model Eloquent diletakkan di `app/Models` (satu tempat, praktik Laravel idiomatik) tetapi "dimiliki" oleh satu domain — didokumentasikan lewat docblock `@domain`. Repository pattern **tidak** dipakai default (over-engineering) — Eloquent + Query scopes cukup; ditinjau ulang bila query lintas-agregat jadi kompleks.

---

## ADR-003 — PostgreSQL + UUID v7 primary key

**Keputusan.** Semua tabel domain memakai `uuid` PK (default `Str::uuid7()` untuk keterurutan waktu). FK memakai `uuid`. Tabel pivot murni Laravel (cache, jobs, sessions) mengikuti default framework.

**Alasan.** Master prompt §Database; Spec §7. UUID: aman untuk sinkronisasi offline (klien membuat ID sendiri sebelum sync, tanpa tabrakan), tidak membocorkan volume data, aman untuk ekspor lintas instansi. UUID v7 menjaga loksplitas indeks B-tree.

**Konsekuensi.**
- Klien PWA meng-generate UUID untuk draft observasi luring → server menerima ID apa adanya (idempoten pada retry).
- Index tambahan pada kolom FK & kolom filter laporan (lihat `database.md`).
- FK `ON DELETE RESTRICT` untuk relasi domain inti (siklus, observasi); `SET NULL`/cascade hanya untuk data turunan yang jelas (mis. notifikasi). Soft delete (`deleted_at`) untuk `users`, `supervision_cycles`, `instruments`, dan data yang mungurangi jejak historis.

---

## ADR-004 — State machine siklus eksplisit, 10 status, 1:1 ke 6 tahap operasional

**Keputusan.** Enum `CycleStatus` (0–9 sesuai Spec §5). Transisi dikelola satu kelas `CycleStateMachine` (guard + transition + event). Tidak ada status di luar daftar. Lihat `state-machine.md`.

**Alasan.** Spec §2.2 & §5 — traceability ke Acheson & Gall (1997) adalah syarat metodologis Artikel 1 & 3. Master prompt §6.

**Konsekuensi.** Setiap transisi menulis satu baris `cycle_status_transitions` (actor, from, to, reason, metadata, timestamp) **dan** satu baris `audit_logs`. Status `6 Tindak Lanjut Terlambat` dipicu job terjadwal, bukan aksi manusia.

---

## ADR-005 — Server-driven UI: Blade + Livewire, tanpa SPA

**Keputusan.** Semua layar dengan Livewire 3 + Tailwind + Alpine. Tidak ada React/Vue. Tidak ada Inertia.

**Alasan.** Spec §3.2, master prompt §3. Interaktivitas yang dibutuhkan (form autosave, wizard, dashboard filter, checklist) semuanya terjangkau Livewire. Mengurangi permukaan build & maintenance.

**Konsekuensi.** Layar **Observasi** adalah pengecualian parsial: butuh kemampuan **penuh luring**, yang tidak bisa dilayani round-trip Livewire. Lihat ADR-006.

---

## ADR-006 — Offline-first hanya pada jalur lapangan, dengan sync queue sungguhan

**Keputusan.** PWA (installable, Service Worker via Workbox). Dua kelas layar:
1. **Online-only** (mayoritas): dashboard, perencanaan, analisis, laporan, admin — Livewire biasa.
2. **Offline-capable**: **Layar Observasi (M2)** dan **unggah bukti RTL (M5)** — ditulis sebagai modul Alpine/JS mandiri yang:
   - menyimpan state form ke **IndexedDB** (bukan sekadar draft di memori),
   - punya **outbox/sync queue** dengan status per item (`local` → `queued` → `syncing` → `synced` / `conflict`),
   - **retry** dengan backoff saat online kembali,
   - **deteksi konflik**: server menolak bila `observation.version` berubah; klien menampilkan diff untuk resolusi manual,
   - endpoint sync idempoten (UUID klien = PK).
   - UI menampilkan indikator: `Luring · Tersimpan lokal · Menunggu sinkron · Menyinkronkan · Tersinkron`.

**Alasan.** Spec §9 ("layar Observasi dan Tindak Lanjut dirancang mobile-first dan mampu bekerja luring"); §3.2; latar 3T Mahakam Ulu. Master prompt §13 melarang klaim "offline" tanpa mekanisme sinkron sesungguhnya.

**Konsekuensi.** Observasi butuh API JSON tersendiri (lihat ADR-008). Media besar (rekaman video) **tidak** di-cache penuh luring — hanya metadata + antrian unggah tertunda; file diunggah saat online (chunked/resumable bila memungkinkan). Batas ini didokumentasikan sebagai *known limitation*, bukan disembunyikan.

---

## ADR-007 — Autentikasi: session (web) + Sanctum token (PWA sync)

**Keputusan.** Laravel Breeze/Fortify-style session auth untuk UI. Laravel **Sanctum** untuk endpoint sync PWA (token per device, dapat dicabut). Password hashing `bcrypt`/`argon2id`. 2FA opsional di fase lanjut.

**Alasan.** Master prompt §3 ("Sanctum jika diperlukan"). PWA offline butuh kredensial tahan-lama yang aman & dapat dicabut per perangkat (kehilangan HP di lapangan).

**Konsekuensi.** Rate limiting berbeda untuk login vs sync. Token sync di-scope minimal (`observation:sync`, `follow-up:evidence`).

---

## ADR-008 — Permukaan API: internal-first, selaras endpoint Spec §8

**Keputusan (perlu konfirmasi checkpoint — lihat risk R-03).** Bangun API JSON di `routes/api.php` yang:
- **wajib**: melayani sinkronisasi PWA (observasi, bukti RTL),
- **memetakan** endpoint inti Spec §8 (`POST /cycles`, `PATCH /cycles/{id}/schedule`, dst.) sebagai API resmi berversi (`/api/v1/...`),
- konsisten: envelope response `{ data, meta, errors }`, kode HTTP benar, `FormRequest` validation, `Policy` authorization, uji fitur per endpoint.

Tidak membangun dokumentasi publik/partner API eksternal pada MVP kecuali diminta.

**Alasan.** Spec §8 mencantumkan endpoint sebagai bagian spesifikasi. Master prompt §11 meminta endpoint tersebut ada + teruji. Namun Spec §13 melarang mengunci API final untuk modul Provisional (analysis/feedback/follow-up) — maka endpoint modul tersebut ditandai `@provisional` dan boleh berubah.

**Konsekuensi.** Sebagian besar aksi punya dua pintu (Livewire action + API controller) yang **memanggil Action/Service domain yang sama** — logika bisnis tidak diduplikasi.

---

## ADR-009 — AI sebagai abstraction layer, default Mock, output selalu `draft`

**Keputusan.** Interface `App\Domain\Ai\Contracts\AiProvider` dengan implementasi `MockAiProvider` (default), `OpenAiProvider`, `AnthropicProvider`. Dipilih via `config('ai.provider')`. Setiap keluaran AI disimpan di `ai_generations` dengan: `prompt_version`, `model`, `provider`, `generated_at`, `source_reference` (entitas sumber), `output`, `reviewer_id`, `review_status` (`draft` → `accepted`/`edited`/`rejected`).

**Alasan.** Master prompt §16; Spec §6.6, §10, §11. Human-in-the-loop wajib. MVP tidak boleh bergantung pada kunci API pihak ketiga atau biaya token untuk berjalan/diuji.

**Konsekuensi.**
- Tidak ada jalur kode di mana keluaran AI mengubah status siklus, mengunci analisis, atau mengirim umpan balik tanpa aksi eksplisit manusia.
- `MockAiProvider` deterministik (berbasis template + data nyata siklus) → uji otomatis stabil.
- Panggilan AI berjalan sebagai **Job** (antrean), tidak memblok request.

---

## ADR-010 — Audit log append-only

**Keputusan.** Tabel `audit_logs` (actor_id, actor_role, action, auditable_type, auditable_id, old_values, new_values, ip, user_agent, context, created_at). Tanpa `updated_at`/`deleted_at`. Tidak ada route/Policy yang mengizinkan update/delete. Ditulis via model observer + dari `CycleStateMachine`.

**Alasan.** Spec §7, §10; master prompt §15; UU 27/2022.

**Konsekuensi.** Pertumbuhan tabel diterima; disediakan job arsip (pindah ke tabel cold storage) — bukan hapus. Perubahan pada data pribadi guru mencatat `old/new` minimal (hindari menyimpan konten sensitif berlebihan di log).

---

## ADR-011 — Model organisasi (perlu konfirmasi checkpoint — risk R-02)

**Asumsi kerja.** Hirarki: `Dinas` (kabupaten/kota) → `Sekolah` → `User`. Field `wilayah`/`kecamatan` opsional pada `Sekolah` untuk filter laporan. Satu instans melayani banyak dinas (multi-tenant lunak berbasis scoping, bukan database terpisah).

**Alasan.** Spec §4, §8 (`/reports/aggregate` "lintas sekolah"), §9 (filter wilayah/periode), konteks Mahakam Ulu.

**Konsekuensi bila salah.** Bila ternyata single-dinas, kolom `dinas_id` tetap ada (nilai konstan) — tanpa rework. Bila butuh level provinsi, tambah tabel induk — additive.

---

## ADR-012 — Model peran Supervisor tunggal + atribut scope

**Keputusan.** Satu role `supervisor` dengan `supervisor_type` ∈ {`kepala_sekolah`, `pengawas`}. Lingkup binaan ditentukan tabel `supervisor_assignments` (supervisor → guru, periode). `pengawas` boleh lintas sekolah dalam satu dinas; `kepala_sekolah` dibatasi sekolahnya.

**Alasan.** Spec §4 menggabungkan keduanya sebagai "Supervisor" dengan hak identik ("CRUD penuh atas siklus supervisi binaannya"). Perbedaan hanya cakupan, bukan kapabilitas. Menghindari duplikasi Policy.

**Konsekuensi.** RBAC scoping (lihat `rbac.md`) berbasis `supervisor_assignments` + batas sekolah/dinas, bukan nama role.

---

## ADR-013 — Instrumen Format A–E: skema generik berbasis JSON, versioned

**Keputusan.** `instruments` (`code` A–E dan kustom, `version`, `schema_json`, `status`, `published_at`). `schema_json` mendefinisikan seksi → item → tipe jawaban (skala Likert, skor numerik, ya/tidak, teks, checklist). Jawaban disimpan EAV di `observation_responses` (`observation_id`, `instrument_id`, `item_key`, `value_numeric`, `value_text`, `value_json`).

**Alasan.** Spec §7 ("pola EAV"), §6.2 ("dapat dikustomisasi mengikuti kebijakan daerah/SNP"). Artikel 2 (validasi Format A–E) **belum dimulai** — struktur item final belum ada. Master prompt §10 melarang skema kaku.

**Konsekuensi.** Skoring (M3) adalah fungsi atas `schema_json` + `observation_responses`, dikonfigurasi bukan hardcoded. Seed menyediakan **Format B placeholder** yang jelas ditandai contoh, bukan instrumen tervalidasi.

---

## ADR-014 — Testing gate per modul

**Keputusan.** Modul "selesai" (master prompt §22) hanya bila: migration + model + FormRequest + Policy + business logic + Livewire UI + error handling + audit (bila relevan) + notifikasi (bila relevan) + **test** (unit + feature + security relevan) + responsive + terdokumentasi. Pest sebagai test runner.

**Alasan.** Master prompt §17, §22, §26.

**Konsekuensi.** Setiap PR fase menyertakan hasil test dan bagian "Known limitations".

---

## Keputusan yang ditunda (butuh input SLR / user — lihat `risk-register.md`)

| Ref | Pertanyaan | Blok |
|---|---|---|
| R-01 | Bangun Fase 3 (M3–M6, M18) sekarang dengan desain provisional, atau berhenti setelah Fase 2? | Fase 3 |
| R-02 | Kedalaman model organisasi (single vs multi-dinas, perlu level provinsi?) | Fase 1 schema |
| R-03 | Permukaan API: sync internal saja, atau REST publik penuh + dokumentasi? | Fase 2+ |
| R-04 | Cakupan luring: hanya Observasi, atau termasuk refleksi guru & bukti RTL? | Fase 2 |
| R-05 | Struktur item Format A–E final menunggu Artikel 2 | M3 skoring |
