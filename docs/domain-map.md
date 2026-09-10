# Domain Map

Pemetaan 18 modul Spec §6 → domain modular monolith → tahap siklus Spec §2.2 → evidence status.

## Tahap operasional (jangkar teoretis — Acheson & Gall 1997, Spec §2.2)

| # | Tahap Makro | Sub-kode | Tahap Operasional Sistem | Status siklus terkait |
|---|---|---|---|---|
| 1 | Pra-observasi | — | **Perencanaan** | 0 Draf → 1 Terjadwal |
| 2 | Observasi | — | **Observasi Pembelajaran** | 2 Observasi Selesai |
| 3 | Pasca-observasi | 3a | **Analisis Hasil Observasi** | 3 Analisis Selesai |
| 3 | Pasca-observasi | 3b | **Pemberian Umpan Balik** | 4 Umpan Balik Diberikan |
| 3 | Pasca-observasi | 3c | **Tindak Lanjut** | 5 Berjalan / 6 Terlambat |
| 3 | Pasca-observasi | 3d | **Pelaporan Berbasis Data** | 7 Dilaporkan → 8 Diarsipkan |

## Domain → Modul → Tahap

| Domain (`app/Domain/*`) | Modul | Tahap | Evidence Status | Fase |
|---|---|---|---|---|
| **Identity** | M13 Identitas & Manajemen Pengguna | — | Confirmed | 1 |
| **Organization** / **Administration** | M15 Administrasi & Konfigurasi Kebijakan | — | Confirmed | 1 |
| **Audit** | M16 Keamanan & Audit Log | — | Confirmed | 1 |
| **Notification** | M14 Notifikasi & Pengingat | Lintas tahap | Confirmed | 1 |
| **Support** | M17 Bantuan & Dukungan Pengguna | — | Confirmed | 1 |
| **Supervision** | (inti) siklus + state machine | Semua | Confirmed | 1–2 |
| **Planning** | M1 Perencanaan Supervisi | Perencanaan | Confirmed | 2 |
| **Observation** | M2 Observasi Pembelajaran | Observasi | Confirmed | 2 |
| **Instruments** | M8 Bank Indikator & Instrumen | Perencanaan, Observasi | Confirmed | 2 |
| **Program** | M7 Manajemen Program Tahunan (pemilik = supervisor, F4-01) | Lintas tahap | Confirmed | 4 ✅ |
| **Analysis** | M3 Analisis Hasil Observasi | Analisis | **Provisional** | 3 ⛔ |
| **Feedback** | M4 Pemberian Umpan Balik | Umpan Balik | **Provisional** | 3 ⛔ |
| **FollowUp** | M5 Tindak Lanjut (RTL) | Tindak Lanjut | **Prioritas Tinggi\*** | 3 ⛔ |
| **Reporting** | M6 Pelaporan Berbasis Data | Pelaporan | **Prioritas Tinggi\*** | 3 ⛔ |
| **Ai** | M18 Asisten AI Supervisi | Analisis, Umpan Balik | **Provisional** | 3 ⛔ |
| **ProfessionalDev** | M9 Katalog PKB | Tindak Lanjut | Provisional | 4 |
| **ProfessionalDev** | M10 Perpustakaan Praktik Baik | Tindak Lanjut | Provisional | 4 |
| **Accountability** | M11 Akuntabilitas Supervisor 360° | Umpan Balik | Provisional | 4 |
| **Accountability** | M12 Kalibrasi Antar-Penilai | Analisis | Provisional (hanya bila multi-supervisor) | 4 |
| **Evaluation** | Panel evaluasi ahli (CVR/Aiken's V/SUS) — DSR Artikel 3 | — (non-siklus) | — | 5 |

\* *Prioritas Tinggi* = dugaan awal SLR bahwa tindak lanjut & pelaporan adalah mata rantai terlemah; **wajib dikonfirmasi ulang** setelah RQ2/RQ3 (Spec §6.1, §13).

⛔ = Spec §13.1 & "Untuk Ditindaklanjuti": jangan mengunci desain DB/API final untuk modul Provisional sampai Gate 6/7 SLR. Lihat `risk-register.md` R-01.

## Aturan ketergantungan antar-domain

```
Identity ──┐
Organization ──┼──> Supervision ──> Planning ──> Observation ──> Analysis ──> Feedback ──> FollowUp ──> Reporting
Audit  ◄───────┘ (semua domain menulis audit)                     │            │           │
Notification ◄──────────────────────────────────────────────────── (event-driven, semua tahap)
Instruments ──> Planning (pilih instrumen), Observation (isi instrumen), Analysis (skema skoring)
Ai ──> Analysis (draft), Feedback (draft saran)  [read-only ke data siklus, tulis hanya ke ai_generations]
Program ──> Supervision (CreateCycle → siklus DRAFT massal; tak menyentuh state machine)
ProfessionalDev ──> (baca analysis_findings/follow_up via query tabel; tulis pkb_*/best_practices)
Accountability ──> (baca supervision_cycles/instrument_versions; tulis supervisor_evaluations/calibration_*)
Evaluation ──> (terisolasi total dari domain siklus; peran `ahli` non-siklus; tulis evaluation_*/expert_reviews)
```

**Larangan ketergantungan:**
- Domain tahap awal **tidak boleh** meng-`import` domain tahap lanjut (Planning tidak tahu Reporting).
- `Ai` tidak boleh menulis ke tabel domain manapun selain `ai_generations`.
- Komunikasi lintas domain: panggilan **Action/Service** atau **domain event** — tidak ada query langsung ke tabel domain lain dari Livewire component domain berbeda.

## Kepemilikan entitas per domain

| Domain | Entitas dimiliki |
|---|---|
| Identity | `users`, `roles`, `permissions`, `role_user`, `personal_access_tokens` |
| Organization | `dinas`, `sekolah`, `supervisor_assignments` |
| Supervision | `supervision_cycles`, `cycle_status_transitions` |
| Planning | `planning_agreements`, `teacher_reflections` |
| Observation | `observations`, `observation_responses`, `observation_media`, `observation_sync_log` |
| Analysis | `analysis_results`, `analysis_findings` |
| Feedback | `feedback_sessions`, `feedback_messages`, `feedback_agreements` |
| FollowUp | `follow_up_plans`, `follow_up_items`, `follow_up_evidence` |
| Reporting | `reports`, `report_snapshots` |
| Instruments | `instruments`, `instrument_versions` |
| Program | `annual_programs`, `program_targets` |
| ProfessionalDev | `pkb_catalog_items`, `pkb_recommendations`, `best_practices` |
| Accountability | `supervisor_evaluations`, `calibration_sessions`, `calibration_participants`, `calibration_scores` |
| Evaluation | `evaluation_panels`, `panel_experts`, `expert_reviews` |
| Notification | `notifications` (Laravel), `reminder_schedules` |
| Administration | `policy_settings`, `indicator_configs` |
| Audit | `audit_logs` |
| Support | `help_articles`, `support_tickets` |
| Ai | `ai_generations`, `ai_prompt_templates` |
