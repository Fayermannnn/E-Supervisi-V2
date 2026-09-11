# Risk Register & Open Questions (Checkpoint)

Skala: Dampak (Tinggi/Sedang/Rendah) × Kemungkinan. Status: OPEN / MITIGATED / ACCEPTED.

---

## ✅ Keputusan checkpoint (disetujui user, 2026-09-09)

| Ref | Keputusan |
|---|---|
| **R-01** | **Lanjut Fase 3 sekarang** — M3–M6 & M18 dibangun dengan business rule minimal, semua entitas/endpoint ditandai `@provisional`, hanya perubahan **additive** setelah SLR Gate 6/7. Iterasi didokumentasikan sebagai design cycle sah di manuskrip DSR (Spec §14). |
| **R-02** | **Multi-dinas via scoping** — hirarki Dinas (kabupaten/kota) → Sekolah → User; field `wilayah`/`kecamatan` di sekolah; tanpa level provinsi (additive bila perlu). |
| **R-03** | **API internal-first** — `/api/v1` memetakan Spec §8 + endpoint sync PWA; controller = shell tipis atas Action domain; tanpa portal dokumentasi publik di MVP. |
| **R-04** | **Offline: Observasi + bukti RTL** — konsol observasi (M2) penuh offline + antrean bukti tindak lanjut (M5); refleksi guru = online. |

R-05 tetap OPEN (menunggu Artikel 2 — dimitigasi dengan instrumen schema-driven).

---

## Detail opsi checkpoint (arsip — untuk konteks keputusan di atas)

### R-01 — Modul Provisional vs instruksi "bangun end-to-end" · Dampak: **TINGGI**

**Konflik nyata.** Spec §13 + "Untuk Ditindaklanjuti": *"jangan mengunci desain database/API final berbasis modul yang masih Provisional"* — Fase 3 (M3–M6, M18) **diblokir** sampai Gate 6/7 SLR. Master prompt menuntut sistem end-to-end sampai pelaporan + menyebut M5 sebagai prioritas.

**Opsi:**
- **(a)** Bangun Fase 3 sekarang: business rule minimal, semua entitas/endpoint `@provisional`, hanya perubahan additive setelah SLR. Rework diperkirakan terbatas (skoring config, struktur finding, template umpan balik). → sistem end-to-end lebih cepat, sesuai master prompt.
- **(b)** Berhenti setelah Fase 2 (Confirmed), lanjut M7 (Confirmed) di Fase 4, tunggu SLR untuk Fase 3. → paling patuh Spec, risiko rework nol, tapi tak ada alur pelaporan sampai SLR selesai.

**Rekomendasi:** **(a)** dengan disiplin `@provisional` + additive-only + titik revisi Gate 6/7 didokumentasikan di manuskrip DSR sebagai iterasi desain yang sah (Spec §14). **Butuh persetujuan user.**

### R-02 — Kedalaman model organisasi · Dampak: **TINGGI** (skema Fase 1)

Spec menyebut "lintas sekolah/kabupaten" & filter wilayah, konteks Mahakam Ulu.
**Pertanyaan:** (1) satu instans untuk banyak dinas/kabupaten, atau satu dinas saja? (2) perlu level provinsi di atas dinas? (3) `admin_dinas` = per kabupaten?
**Asumsi kerja (ADR-011):** multi-dinas via scoping; hirarki Dinas→Sekolah→User; `wilayah`/`kecamatan` sebagai field sekolah; tanpa level provinsi (additive bila perlu).
**Mitigasi:** `dinas_id` selalu ada meski single-tenant → tak ada rework bila asumsi meleset ke bawah.

### R-03 — Permukaan API · Dampak: **SEDANG–TINGGI**

**Pertanyaan:** API hanya untuk sinkronisasi PWA internal, atau REST publik penuh (semua endpoint Spec §8) + dokumentasi + berversi untuk kemungkinan klien pihak ketiga / mobile native nanti?
**Asumsi kerja (ADR-008):** bangun `/api/v1` yang memetakan Spec §8 + endpoint sync; tanpa portal dokumentasi publik di MVP; controller = shell tipis atas Action domain.
**Konsekuensi bila "publik penuh":** tambah OpenAPI spec, rate-plan, versioning policy, contract test — ~1 minggu kerja ekstra.

### R-04 — Cakupan offline · Dampak: **SEDANG** (Fase 2)

Spec §9 menyebut **Observasi & Tindak Lanjut** mobile-first + luring.
**Pertanyaan:** MVP offline mencakup: (a) hanya konsol Observasi, (b) Observasi + unggah bukti RTL, (c) + refleksi guru pra-observasi?
**Asumsi kerja (ADR-006):** (b). Refleksi guru = online (draft lokal browser sederhana, bukan sync queue penuh).
**Mitigasi:** arsitektur outbox generik → menambah entitas offline lain = konfigurasi, bukan rewrite.

### R-05 — Struktur item Format A–E belum tervalidasi · Dampak: **SEDANG**

Artikel 2 (validasi psikometrik Format A–E) **belum dimulai**. Hanya "Format B" yang disebut untuk observasi.
**Mitigasi (ADR-013):** instrumen sepenuhnya schema-driven (`schema_json` + `scoring_config`); seed Format B *placeholder* bertanda "CONTOH — belum tervalidasi"; skoring adalah fungsi konfigurasi. Perubahan struktur item pasca-validasi = data, bukan migrasi.

---

## Risiko teknis & proyek

| ID | Risiko | Dampak | Mitigasi | Status |
|---|---|---|---|---|
| T-01 | Offline sync bug → kehilangan/duplikasi data observasi (data berdampak pada penilaian guru) | Tinggi | UUID klien = PK (idempoten); unik `(observation_id,item_key)`; optimistic lock `version`; deteksi konflik + resolusi manual; browser test skenario luring; tidak ada auto-merge diam-diam | MITIGATED (desain) |
| T-02 | Bias/kesalahan output AI memengaruhi penilaian kinerja guru | Tinggi | Semua output `draft`; human-in-the-loop gate di state machine; AI tak akses DB langsung; prompt/model tercatat; MockAiProvider default | MITIGATED (desain) |
| T-03 | Kebocoran data pribadi lintas peran (IDOR, cross-school) | Tinggi | Default deny; Policy + global scope; route-model-binding; suite `tests/Feature/Security`; audit `authorization.denied` | MITIGATED (desain) |
| T-04 | Kepatuhan UU 27/2022 PDP (basis pemrosesan, retensi, enkripsi) | Tinggi | Header keamanan respons (CSP nonce+hash, HSTS, X-Frame-Options, Referrer/Permissions-Policy) via `SecureHeaders` middleware — diuji (ADR-016); `FORCE_HTTPS`/`TRUSTED_PROXIES`/`SESSION_SECURE_COOKIE` siap pakai untuk TLS produksi — diuji; enkripsi at-rest utk berkas observasi (operasional); retensi = arsip bukan hapus; audit menyeluruh; dokumen basis pemrosesan (pelaksanaan tugas dinas) di `deployment.md` | OPEN (sisa: review hukum + basis pemrosesan + enkripsi at-rest deployment — di luar kendali kode) |
| T-05 | Rework karena SLR mengubah prioritas/modul Fase 3 | Sedang | Lihat R-01; `@provisional`; additive-only; titik revisi Gate 6/7 | ACCEPTED (bila R-01=a) |
| T-06 | Modular monolith → batas domain luntur seiring waktu | Sedang | Konvensi namespace; Larastan + arch test (deptrac/pest-arch) melarang import lintas domain terlarang; code review | MITIGATED |
| T-07 | Media besar (video) di lapangan 3T tak terunggah | Sedang | Metadata dulu, file antre; unggah chunked/resumable saat online; UI jujur soal status; bukan diklaim "cached offline" | ACCEPTED (known limitation) |
| T-08 | Solution-first bias merusak kredibilitas DSR Artikel 3 | Sedang | Dokumentasikan problem identification bersumber evidence map Artikel 1; iterasi versi lama→v2 sebagai design cycle sah (Spec §14) | MITIGATED (proses) |
| T-09 | Performa dasbor & laporan agregat pada volume besar | Sedang | Index terencana (`database.md`); `report_snapshots` materialisasi; uji `explain`; paginasi | MITIGATED (desain) |
| T-10 | Scope creep 18 modul sekaligus | Sedang | Roadmap berfase + exit criteria + checkpoint; RULE 3 (tiap fitur ada dasar requirement) | MITIGATED (proses) |
| T-11 | Lingkungan: PHP 8.4 (Herd) vs "8.3+" spec; PostgreSQL 16 lokal | Rendah | 8.4 kompatibel Laravel terbaru; kunci versi di `composer.json` `>=8.3`; CI uji 8.3 & 8.4 | ACCEPTED |
| T-12 | Kompleksitas Livewire untuk konsol observasi offline | Sedang | Konsol observasi = modul Alpine/JS mandiri + API, bukan Livewire round-trip (ADR-006) | MITIGATED |
| T-13 | Backup & disaster recovery belum dirancang | Sedang | `deployment.md` Fase 5: pg_dump terjadwal + retensi berkas; uji restore | OPEN |

---

## Asumsi yang didokumentasikan (RULE 9 — bukan keputusan final)

1. Bahasa antarmuka: Indonesia tunggal (tanpa i18n multi-bahasa di MVP).
2. Satu tahun ajaran aktif per siklus; siklus tidak lintas tahun ajaran.
3. Satu guru bisa punya banyak siklus paralel dalam satu tahun ajaran (mis. per semester / per fokus).
4. Observasi bisa >1 per siklus (sinkron + asinkron), tetapi finalisasi tahap butuh minimal satu `final`.
5. `admin_sistem` tidak melihat konten pedagogis (catatan observasi, umpan balik) di UI-nya — hanya metadata teknis.
6. Notifikasi MVP: in-app + email. WhatsApp/SMS gateway = fase lanjut (relevan untuk 3T, tapi butuh biaya & vendor).
7. Ekspor laporan MVP: PDF dulu; XLSX & CSV menyusul dalam Fase 3.
8. AI default `MockAiProvider`; provider nyata (OpenAI/Anthropic) hanya aktif bila kunci API dikonfigurasi.
