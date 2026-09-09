# E-Supervisi Klinis Pendidikan — v2.0

Platform digital untuk mendukung **siklus supervisi klinis pendidikan** secara end-to-end:
Perencanaan → Observasi Pembelajaran → Analisis → Umpan Balik → Tindak Lanjut → Pelaporan Berbasis Data.

Dibangun **dari nol** mengacu pada `Spesifikasi_E-Supervisi_v2_Evidence-Informed.docx` (source of truth).
Sistem versi lama (17 modul, 10-status generik) **deprecated** dan tidak dijadikan baseline.

## Status

**PHASE 0 — Discovery & Architecture (selesai, menunggu checkpoint).**
Belum ada kode aplikasi. Dokumen arsitektur baseline ada di `docs/`.

| Fase | Fokus | Modul | Status |
|---|---|---|---|
| 0 | Discovery & architecture baseline | — | ✅ Dokumen siap, menunggu keputusan checkpoint |
| 1 | Fondasi: identitas, RBAC, organisasi, audit, notifikasi | M13, M14, M15, M16, M17 | ⏳ Menunggu approval |
| 2 | Inti siklus: Perencanaan → Observasi | M1, M2, M8 | ⏳ |
| 3 | Analisis → Pelaporan (+ AI) | M3, M4, M5, M6, M18 | ⛔ Diblokir spec sampai keputusan checkpoint (lihat `docs/risk-register.md` R-01) |
| 4 | Pengembangan profesional & akuntabilitas | M7, M9, M10, M11, M12 | ⏸️ |
| 5 | Kesiapan evaluasi ahli (DSR Artikel 3) | — | ⏸️ |

## Stack

- **Backend:** PHP 8.3+ / Laravel (stabil terbaru), modular monolith
- **DB:** PostgreSQL, UUID primary keys
- **Frontend:** Blade + Livewire + Tailwind + Alpine
- **Offline:** PWA — Service Worker + IndexedDB + sync queue (fokus: layar Observasi & bukti Tindak Lanjut)
- **AI:** abstraction layer (`AiProvider` interface), default `MockAiProvider`; output selalu berstatus `draft`

## Dokumen arsitektur

| Dokumen | Isi |
|---|---|
| [docs/architecture.md](docs/architecture.md) | Architecture Decision Records (ADR) |
| [docs/domain-map.md](docs/domain-map.md) | Peta domain modular monolith, 18 modul → domain |
| [docs/database.md](docs/database.md) | ERD, entitas inti, pola EAV instrumen |
| [docs/rbac.md](docs/rbac.md) | Matriks aktor × aksi, scoping data |
| [docs/state-machine.md](docs/state-machine.md) | State machine siklus (10 status), transition rules |
| [docs/api.md](docs/api.md) | Route map (web + API) |
| [docs/screen-map.md](docs/screen-map.md) | Screen map & MVP UI priorities |
| [docs/backlog.md](docs/backlog.md) | Product backlog + acceptance criteria |
| [docs/roadmap.md](docs/roadmap.md) | Implementation roadmap per fase |
| [docs/risk-register.md](docs/risk-register.md) | Risk register + open questions untuk checkpoint |
| [docs/offline.md](docs/offline.md) | Strategi offline-first (draft, akan diperdalam di Fase 2) |
| [docs/ai.md](docs/ai.md) | Desain abstraction layer AI (draft) |
| [docs/testing.md](docs/testing.md) | Strategi pengujian (draft) |
| [docs/deployment.md](docs/deployment.md) | Deployment (draft) |

## Prinsip non-negosiasi

1. **Evidence-first** — prioritas modul mengikuti gap SLR, bukan asumsi fitur.
2. **Human-in-the-loop** — AI menyusun draf, tidak pernah menjadi pengambil keputusan final.
3. **Offline-first** untuk aktivitas lapangan (Observasi, bukti RTL).
4. **RBAC ketat** — guru hanya datanya; supervisor hanya binaannya; Admin Dinas hanya agregat.
5. **Audit trail append-only** pada semua perubahan status & data sensitif (UU 27/2022 PDP).
6. **Traceable ke kerangka teoretis** Acheson & Gall (1997) — 6 tahap operasional.
7. Modul **Provisional tidak dikunci** di skema DB/API final.
