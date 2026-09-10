# Dokumentasi DSR — Artefak Sistem E-Supervisi Klinis Pendidikan v2.0

> Untuk **Artikel 3** (Design Science Research). Menstrukturkan pengembangan
> sistem mengikuti enam aktivitas Peffers dkk. (2007). Sumber kebenaran:
> `Spesifikasi_E-Supervisi_v2_Evidence-Informed.docx` (§14 Catatan Metodologis).

---

## 1. Problem identification & motivation

**Masalah.** Supervisi klinis pendidikan di Indonesia dijalankan secara manual
dan terfragmentasi antar tahap; mata rantai **tindak lanjut** dan **pelaporan
berbasis data** diduga paling lemah, terutama pada wilayah dengan infrastruktur
terbatas (3T, mis. Mahakam Ulu).

**Sumber masalah = bukti, bukan asumsi.** Identifikasi masalah bersumber dari
*evidence map* Artikel 1 (SLR — RQ2/RQ3: tahap siklus mana yang paling minim
dukungan digital), bukan observasi informal atau preferensi pengembang. Selama
SLR belum tuntas (Gate 6/7), modul yang bergantung pada temuan tersebut
(M3–M6, M9–M12, M18) ditandai **`@provisional`** — desain sengaja dibangun agar
perubahan setelah bukti final bersifat *additive*, bukan *rework*
(`docs/risk-register.md` R-01, T-05).

**Kerangka teoretis.** Model tiga tahap makro Acheson & Gall (1997)
(pra-observasi, observasi, pasca-observasi), dengan pasca-observasi dipecah
menjadi empat sub-kode operasional — **identik** dengan skema koding ekstraksi
data Artikel 1, sehingga peta gap SLR memetakan langsung ke modul sistem.

## 2. Objectives of a solution

Sistem harus:

1. Menyediakan **satu platform** untuk keenam tahap operasional siklus supervisi
   klinis (`docs/domain-map.md`).
2. Memprioritaskan tahap yang secara bukti paling minim dukungan digital
   (tindak lanjut, pelaporan) — bukan tahap yang sudah difasilitasi alat lain.
3. **Tetap dapat digunakan pada infrastruktur terbatas** — arsitektur PWA dengan
   mode luring untuk aktivitas lapangan (observasi, bukti tindak lanjut).
4. Menjaga **human-in-the-loop** pada seluruh kapabilitas AI — sistem menyusun
   draf, tidak mengambil keputusan profesional.
5. **Traceable** ke kerangka teoretis: setiap modul, status, dan kebutuhan data
   dapat ditelusuri balik ke salah satu dari enam tahap.
6. Menghasilkan artefak yang **dapat dievaluasi ahli** sebagai bagian siklus DSR.

### Prinsip non-negosiasi (jadi kriteria evaluasi)

| # | Prinsip | Wujud di kode |
|---|---|---|
| 1 | Evidence-first | Prioritas modul = gap SLR; modul provisional bertanda `@provisional` |
| 2 | Human-in-the-loop | `App\Domain\Ai` tanpa akses DB; keluaran selalu `draft`; tak memicu transisi (arch test) |
| 3 | Offline-first pada jalur lapangan | Konsol observasi + bukti RTL: IndexedDB + outbox + sync idempoten (ADR-006) |
| 4 | RBAC ketat, default deny | `RolePermissionMap` + Policy + global scope; matriks `rbac.md` diuji parametrik |
| 5 | Audit trail append-only | `audit_logs` tanpa route tulis; `CycleStateMachine` mencatat tiap transisi |
| 6 | Traceable ke Acheson & Gall (1997) | `CycleStatus` 0–9 dipetakan 1:1 ke enam tahap operasional |
| 7 | Modul provisional tidak dikunci | Skema/prompt additive-only setelah Gate 6/7 |

## 3. Design & development

### 3.1 Iterasi desain (bukti proses iteratif yang rigor)

Spesifikasi lama (17 modul, state machine 10-status generik, ~35 endpoint)
disusun **sebelum** kerangka teoretis & protokol SLR dikunci — kelemahan
*solution-first bias*. Sistem v2.0 disusun ulang dari nol dengan kerangka
teoretis sebagai titik tolak. Transisi versi-lama → v2.0 dinarasikan di
manuskrip sebagai **design cycle DSR yang sah**, bukan "kesalahan awal yang
disembunyikan" (Spec §14).

### 3.2 Keputusan arsitektur (ADR)

Lengkap di `docs/architecture.md`. Ringkas:

- **ADR-001/002** Modular monolith, batas domain di `app/Domain/*`, komunikasi
  via Action/Service + domain event (bukan HTTP internal).
- **ADR-003** PostgreSQL + UUID v7 PK (aman untuk sinkronisasi luring).
- **ADR-004** State machine siklus eksplisit — `CycleStateMachine` satu-satunya
  penulis `cycle_status_transitions`; 10 status 1:1 ke enam tahap.
- **ADR-005/006** Blade + Livewire; PWA offline hanya pada jalur lapangan dengan
  outbox/sync queue sungguhan (bukan klaim "cached").
- **ADR-007/008** Session auth (web) + Sanctum token (PWA); API `/api/v1`
  internal-first memetakan Spec §8, controller = shell tipis atas Action.
- **ADR-009** AI abstraction layer, default `MockAiProvider` deterministik,
  output selalu `draft`, review manusia berlapis.
- **ADR-010** Audit log append-only.
- **ADR-013** Instrumen Format A–E schema-driven (`schema_json` + `scoring_config`)
  — struktur item final menunggu Artikel 2, skoring adalah fungsi konfigurasi.
- **ADR-015** (Fase 5) Domain `Evaluation` untuk validasi ahli — terpisah total
  dari domain siklus (arch test), peran `ahli` bukan aktor siklus.

### 3.3 Peta pembangunan berfase

| Fase | Fokus | Modul | Status |
|---|---|---|---|
| 0 | Discovery & architecture baseline | — | ✅ (10 artefak `docs/`) |
| 1 | Fondasi: identitas, RBAC, organisasi, audit, notifikasi | M13–M17 | ✅ `phase1-complete` |
| 2 | Inti siklus: Perencanaan → Observasi (+ PWA luring, API Sanctum) | M1, M2, M8 | ✅ `phase2-complete` |
| 3 | Analisis → Umpan Balik → RTL → Pelaporan (+ AI) | M3–M6, M18 | ✅ `phase3-complete` `@provisional` |
| 4 | Program tahunan, PKB, praktik baik, akuntabilitas 360°, kalibrasi | M7, M9–M12 | ✅ `phase4-complete` (M9–M12 `@provisional`) |
| 5 | Kesiapan evaluasi ahli (dokumen ini) | — | ✅ `phase5-complete` |

## 4. Demonstration

Artefak berjalan **end-to-end** dan dapat didemonstrasikan tanpa kunci API pihak
ketiga (`AI_PROVIDER=mock`). Prosedur lengkap: `docs/demo-script.md`.

Alur yang dapat diperagakan:

1. **Siklus penuh** perencanaan → observasi (matikan jaringan, isi, nyalakan,
   sinkron tanpa duplikat) → analisis (skoring deterministik + tinjau draf AI)
   → umpan balik terstruktur → RTL (+ eskalasi overdue) → laporan per siklus →
   laporan agregat dinas → arsip.
2. **Program tahunan** (M7): supervisor menyemai siklus DRAFT massal untuk
   seluruh guru binaan.
3. **PKB & praktik baik** (M9/M10): rekomendasi PKB otomatis dari area
   pengembangan; nominasi praktik baik → persetujuan guru → kurasi dinas.
4. **Akuntabilitas 360°** (M11): guru menilai proses supervisi; agregat anonim.
5. **Kalibrasi antar-penilai** (M12): beberapa supervisor menskor artefak yang
   sama; sistem menghitung persen kesepakatan & Fleiss' κ.
6. **Evaluasi ahli** (Fase 5): panel ahli menilai artefak; sistem menghitung
   CVR/CVI, Aiken's V, dan SUS.

Seed menyediakan data di **setiap status siklus** + data demo Fase 4–5.

## 5. Evaluation

### 5.1 Evaluasi ahli (expert judgment) — `docs/expert-judgment.md`

- **Panel**: minimal dua rumpun ahli — **manajemen pendidikan** dan **sistem
  informasi** (Spec §14), konsisten dengan pendekatan validasi Artikel 2.
- **Instrumen** (di kode, `App\Domain\Evaluation`):
  - **Relevansi (CVR/CVI, Lawshe 1975)** — 8 aspek artefak dinilai
    esensial/berguna/tidak perlu.
  - **Kualitas (Aiken's V, 1985)** — 8 aspek, skala 1–5.
  - **Usability (SUS, Brooke 1996)** — 10 butir, skor 0–100.
- **Perhitungan** dilakukan sistem (`ExpertJudgmentStats`), deterministik & diuji
  unit (`tests/Unit/ExpertJudgmentStatsTest.php`). Ahli mengisi lewat UI
  (`/evaluation/{panel}/review`); peneliti menutup panel → snapshot statistik.
- **Ambang penerimaan** (dilaporkan, keputusan ada pada peneliti):
  - CVR per aspek ≥ nilai kritis Lawshe untuk jumlah panelis (mis. 0.99 untuk
    N=5–7; 0.62 untuk N=10);
  - Aiken's V ≥ 0.6 (memadai) / ≥ 0.8 (tinggi);
  - SUS ≥ 68 (di atas rata-rata industri) / ≥ 72.6 (kategori B).

### 5.2 Evaluasi teknis — `docs/technical-evaluation.md`

- Ringkasan tinjauan keamanan per fase + status `risk-register.md` (T-01..T-13).
- Inventaris pengujian per kategori (unit/feature/security/arch/performance) —
  line coverage tidak tersedia (tanpa Xdebug/PCOV di lingkungan).
- Batas performa jalur baca pada volume ~200 siklus (Pest, `composer ci`).
- Prosedur uji beban manual untuk lingkungan target.

## 6. Communication

- Tag rilis per fase (`phase1-complete` … `phase5-complete`) + laporan 7-bagian
  per fase (`docs/roadmap.md` §25).
- Dokumen ini + `docs/architecture.md` + `docs/DECISIONS.md` = jejak keputusan
  desain untuk bagian *Design & Development* manuskrip Artikel 3.
- Hasil evaluasi ahli (CVR/CVI, Aiken's V, SUS) + evaluasi teknis = bahan bagian
  *Evaluation*.

### Batasan yang dinyatakan eksplisit (untuk manuskrip)

- Modul provisional belum dikunci ke bukti SLR final (Gate 6/7).
- Instrumen Format A–E belum tervalidasi (Artikel 2 belum dimulai) — Format B di
  sistem adalah *placeholder* bertanda contoh.
- Ekspor laporan MVP = print-to-PDF browser; PDF/XLSX server-side ditunda.
- Belum ada uji lapangan skala penuh; evaluasi Fase 5 adalah expert judgment +
  evaluasi teknis, bukan uji efektivitas di sekolah.
