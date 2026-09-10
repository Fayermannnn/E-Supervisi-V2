# Implementation Roadmap

Sumber: Spec §12, master prompt §9 & §20. Setiap fase menghasilkan laporan 7-bagian (master prompt §25):
1) Implemented features · 2) Files changed · 3) Database changes · 4) Tests · 5) Security review · 6) Known limitations · 7) Next phase.

Workflow tiap fase: **DISCOVER → PLAN → ARCHITECT → DATABASE → BACKEND → FRONTEND → INTEGRATION → TEST → SECURITY REVIEW → UX REVIEW → DOCUMENTATION** (master prompt §20). Checkpoint di akhir tiap fase sebelum lanjut.

---

## PHASE 0 — Discovery & Architecture ✅ (dokumen ini)

**Output:** ADR, domain map, ERD, RBAC matrix, state machine, route map, screen map, backlog, roadmap, risk register.
**Checkpoint aktif:** R-01..R-05 (`risk-register.md`). Butuh keputusan user sebelum Fase 3; R-02/R-03/R-04 mempengaruhi Fase 1–2.

---

## PHASE 1 — Fondasi · M13, M14, M15, M16, M17

**Prasyarat:** tidak ada (Spec §12 & §13.2 — Confirmed, aman dikerjakan sekarang).
**Backlog:** EPIC A (A1–A11).
**Deliverable:**
- Skeleton Laravel + Postgres + Pest + Livewire + Tailwind + Larastan + Pint + CI lokal.
- Auth, RBAC (matriks `rbac.md` sebagai test), organisasi (dinas/sekolah), user, penugasan supervisor–guru.
- Audit log append-only, notifikasi foundation, konfigurasi kebijakan, bantuan.
- Shell layout + design system + navigasi per peran.
- Seed: 1 admin sistem, 1 admin dinas, ≥2 sekolah, ≥3 supervisor, ≥8 guru, penugasan.
**Exit criteria:** semua alur auth+admin lulus feature test; security test (privilege escalation, cross-dinas) hijau; Larastan max bersih; dokumentasi `rbac.md`/`database.md` sinkron dengan kode.

---

## PHASE 2 — Inti Siklus: Perencanaan → Observasi · M1, M2, M8

**Prasyarat:** Fase 1 stabil. Keputusan R-02 (org), R-04 (cakupan offline) sudah ada.
**Backlog:** EPIC B (B1–B10).
**Deliverable:**
- Bank instrumen (schema-driven) + Format B placeholder.
- Buat siklus → perencanaan → kesepakatan → refleksi guru → observasi (online) → **observasi offline + sync queue** → finalisasi → media.
- State machine transisi 0→1→2 lengkap dengan guard, transitions log, audit.
- Dasbor supervisor (S1) & tampilan guru.
**Exit criteria:**
- Alur **Planning → Observation end-to-end** berjalan (master prompt §Phase 2).
- Skenario luring→online: tidak ada kehilangan/duplikasi data (feature + browser test).
- Idempotensi endpoint sync terbukti; konflik → 409 + resolusi.
- Upload media aman (di luar web root, signed access, mime check).

---

## PHASE 3 — Analisis → Pelaporan (+ AI) · M3, M4, M5, M6, M18 — `@provisional`

**Prasyarat (Spec §12):** **MENUNGGU konfirmasi prioritas RQ2/RQ3 (Gate 6/7 SLR).**
**Keputusan checkpoint R-01 diperlukan:** salah satu —
- **(a) Lanjut sekarang** dengan business rule minimal + `@provisional` + skema additive-only setelah dikunci (menerima risiko rework terbatas), **atau**
- **(b) Berhenti setelah Fase 2**, kerjakan Fase 4 item yang Confirmed (M7), tunggu SLR untuk Fase 3.

**Backlog:** EPIC C (C1–C8).
**Deliverable (bila (a)):** skoring & analisis manual, draft AI (Mock default), ruang umpan balik terstruktur, RTL + eskalasi + offline evidence, laporan siklus + agregat, arsip.
**Human-in-the-loop gates (wajib, tak bergantung R-01):**
- `finalizeAnalysis` butuh reviewer manusia; AI tak memicu transisi.
- Umpan balik final ≠ output AI mentah.
- Anomali agregat AI = label untuk tinjauan, bukan aksi.
**Exit criteria:** alur pasca-observasi end-to-end; semua gate AI diuji; RTL overdue/eskalasi diuji pada batas tanggal; ekspor laporan berjalan.

---

## PHASE 4 — Pengembangan Profesional & Akuntabilitas · M7, M9, M10, M11, M12 ✅

**Prasyarat:** Fase 2–3 stabil digunakan (Spec §12).
**Backlog:** EPIC D. Keputusan checkpoint F4-01..F4-04 (`DECISIONS.md`).
**Exit criteria (terpenuhi):**
- ✅ program tahunan (pemilik supervisor) menghasilkan siklus DRAFT massal, idempoten, tanpa mengubah state machine;
- ✅ rekomendasi PKB deterministik terhubung ke area pengembangan analisis + pola RTL berulang;
- ✅ praktik baik: nominasi → persetujuan guru (UU PDP) → kurasi dinas → terbit;
- ✅ 360° dengan ambang anonimitas; tidak memicu transisi siklus;
- ✅ kalibrasi antar-penilai + `CalibrationStats` deterministik (persen kesepakatan, variansi, Fleiss' κ) — mendukung data reliabilitas Artikel 2.
- ✅ `composer ci` hijau (Pint + PHPStan 8 + 192 Pest). Tag `phase4-complete`.

M9–M12 tetap `@provisional` — perubahan skema additive-only setelah SLR Gate 6/7.

---

## PHASE 5 — Kesiapan Evaluasi Ahli (DSR Artikel 3) ✅

**Prasyarat:** MVP Fase 1–4 berjalan (Spec §12). Checkpoint F5-01, F5-02 (`DECISIONS.md`).
**Backlog:** EPIC E.
**Deliverable (terpenuhi):**
- ✅ **E1** `docs/demo-script.md` (6 alur end-to-end, akun demo, perintah terjadwal); seeder diperluas dengan panel evaluasi ahli.
- ✅ **E2** instrumen expert judgment **in-app** (F5-01) — domain `Evaluation` + peran `ahli`; CVR/CVI (Lawshe + tabel nilai kritis), Aiken's V, SUS; deterministik & unit-tested; `docs/expert-judgment.md`.
- ✅ **E3** `docs/dsr-artefak.md` — enam aktivitas Peffers dkk. (2007).
- ✅ **E4** `docs/technical-evaluation.md` (keamanan + risk register, inventaris tes sbg pengganti line coverage, hasil performa, prosedur uji beban `wrk`); `tests/Feature/Performance/` di `composer ci`.
- ✅ `composer ci` hijau (Pint + PHPStan 8 + **214 Pest**). Tag `phase5-complete`.

**Exit criteria (terpenuhi):** artefak berjalan end-to-end, terdokumentasi sebagai DSR, siap dinilai panel ≥ 2 rumpun ahli (manajemen pendidikan + sistem informasi) lewat modul in-app; evaluasi teknis menunjukkan kontrol keamanan inti termitigasi & performa dalam batas pada ~200 siklus. Sisa pekerjaan (review hukum PDP, hardening deployment, browser test, uji beban lapangan) bersifat pra-go-live.

---

## Titik revisi wajib (Spec §13.1)

| Trigger SLR | Aksi pada dokumen/kode |
|---|---|
| GATE 1 (RQ final) | Cek ulang 6 tahap operasional masih konsisten dgn RQ |
| GATE 6 (sintesis tematik & cycle mapping) | **Revisi status Provisional/Prioritas Tinggi Bagian 6 → keputusan final berbasis data**; kunci skema modul Fase 3 |
| GATE 7 (gap & novelty final) | Kunci ulang Roadmap Fase 3 sesuai tahap terbukti paling minim dukungan digital |

## Git & commit (master prompt §21)

Konvensi: `type(domain): ringkas` — mis. `feat(observation): implement offline sync queue`, `test(supervision): add cycle transition guards`. Satu fase = beberapa commit + satu tag `phaseN-complete`.
