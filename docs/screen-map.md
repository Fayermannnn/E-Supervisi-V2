# Screen Map — UI/UX

Sumber: Spec §9, master prompt §12. Prinsip: modern, profesional, institusional (bukan template admin generik), aksesibel, responsif. Layar **Observasi** & **Tindak Lanjut** = mobile-first + offline.

## Layar prioritas MVP (Spec §9)

| # | Layar | Aktor | Fase | Catatan kunci |
|---|---|---|---|---|
| S1 | **Dasbor Siklus Aktif (Supervisor)** | supervisor | 1→2 | Siklus per status; kartu RTL mendekati tenggat & terlambat; progres per tahap; **actionable** (bukan angka mati) |
| S2 | **Formulir Perencanaan** | supervisor + guru | 2 | 1 layar ringkas: fokus, instrumen, jadwal, tombol kesepakatan dua pihak |
| S3 | **Layar Observasi** | supervisor | 2 | Mobile-first, **offline-first**, autosave, indikator progres item, catatan skrip cepat, indikator status sinkron |
| S4 | **Layar Analisis** | supervisor | 3 | Draft AI berlabel jelas **"DRAFT / SARAN AI"** + tombol **"Tinjau & Sunting"**; skor per seksi; temuan |
| S5 | **Ruang Umpan Balik** | supervisor + guru | 3 | Percakapan terstruktur (observasi → pertanyaan reflektif → tanggapan → kesepakatan); konfirmasi guru |
| S6 | **Pelacak Tindak Lanjut** | supervisor + guru | 3 | Checklist RTL, status visual berjalan/terlambat/selesai, unggah bukti, reminder |
| S7 | **Dasbor Pelaporan (Admin Dinas)** | admin_dinas | 3 | Agregat lintas sekolah, filter wilayah/periode, ekspor, deteksi anomali (label saja) |

## Peta layar lengkap per aktor

### Guru
```
Login → Dasbor Guru
  ├─ Jadwal supervisi saya (kartu siklus + status)
  ├─ Siklus {id}
  │   ├─ Refleksi pra-observasi  [isi/edit sebelum observasi]
  │   ├─ Hasil observasi          [read-only, setelah final]
  │   ├─ Umpan balik              [baca, tanya, konfirmasi penerimaan]
  │   └─ Tindak Lanjut            [checklist, unggah bukti]  ← offline-capable
  └─ Profil / Bantuan
```

### Supervisor
```
Login → Dasbor Siklus Aktif (S1)
  ├─ Buat siklus → pilih guru binaan
  ├─ Program tahunan (Fase 4)
  ├─ Siklus {id}  (tab per tahap, mengikuti state machine)
  │   ├─ Perencanaan (S2)
  │   ├─ Observasi (S3)          ← mobile-first + offline
  │   ├─ Analisis (S4)           ← draft AI berlabel
  │   ├─ Umpan Balik (S5)
  │   ├─ Tindak Lanjut (S6)
  │   └─ Laporan siklus
  ├─ Bank instrumen (lihat/pakai)
  └─ Profil / perangkat tersinkron / Bantuan
```

### Admin Dinas
```
Login → Dasbor Pelaporan (S7)
  ├─ Agregat lintas sekolah (filter wilayah/periode/jenjang)
  ├─ Daftar sekolah & ringkasan siklus
  ├─ Bank indikator/instrumen (kelola kebijakan dinas)
  ├─ Ekspor laporan kebijakan (PDF/XLSX/CSV)
  ├─ Anomali ditandai AI (tinjauan)
  └─ Audit log (terbatas)
```

### Admin Sistem
```
Login → Dasbor Sistem
  ├─ Manajemen pengguna (CRUD, reset password, aktif/nonaktif)
  ├─ Organisasi (dinas, sekolah)
  ├─ Penugasan supervisor–guru
  ├─ Konfigurasi & kebijakan
  ├─ Audit log (penuh, read-only)
  └─ Status backup / kesehatan sistem
```

## Layar Fase 4–5 (tambahan)

| Rute | Komponen | Aktor | Fungsi |
|---|---|---|---|
| `/programs`, `/programs/{id}` | `Program\ProgramIndex`/`ProgramEditor` | Supervisor | Program tahunan + generate siklus DRAFT massal (M7) |
| `/pkb/catalog` | `ProfessionalDev\PkbCatalogIndex` | Semua (kelola: admin dinas/sistem) | Katalog PKB (M9) |
| `/cycles/{id}/pkb` | `ProfessionalDev\CyclePkb` | Guru + Supervisor | Rekomendasi PKB + nominasi praktik baik (M9/M10) |
| `/best-practices` | `ProfessionalDev\BestPracticeLibrary` | Semua (kurasi: admin dinas) | Perpustakaan praktik baik + antrean kurasi (M10) |
| `/cycles/{id}/evaluate` | `Accountability\SupervisorEvaluationForm` | Guru | Penilaian 360° proses supervisi (M11) |
| `/accountability` | `Accountability\AccountabilityDashboard` | Supervisor + Admin Dinas | Agregat 360° di atas ambang anonimitas (M11) |
| `/calibration`, `/calibration/{id}` | `Accountability\CalibrationIndex`/`CalibrationShow` | Admin Dinas/Sistem + peserta supervisor | Sesi kalibrasi + statistik reliabilitas (M12) |
| `/evaluation`, `/evaluation/{id}` | `Evaluation\PanelIndex`/`PanelShow` | Admin Sistem + Ahli | Panel evaluasi ahli + hasil CVR/Aiken's V/SUS (Fase 5) |
| `/evaluation/{id}/review` | `Evaluation\ExpertReviewForm` | Ahli | Isi relevansi + kualitas + kuesioner SUS |

## Pola komponen

- **Design system**: Tailwind + token warna institusional (netral + 1 warna aksen). Komponen Blade: `<x-card>`, `<x-stat>`, `<x-status-badge>`, `<x-timeline>`, `<x-cycle-stepper>`, `<x-sync-indicator>`, `<x-ai-draft-banner>`.
- **`<x-cycle-stepper>`**: visualisasi 6 tahap + status saat ini, dipakai di semua layar siklus.
- **`<x-ai-draft-banner>`**: banner kuning "DRAFT / SARAN AI — belum ditinjau" wajib membungkus setiap output AI (Spec §9, §10).
- **`<x-sync-indicator>`**: `Luring · Tersimpan lokal · Menunggu sinkron · Menyinkronkan · Tersinkron` (Spec §13).
- **Dasbor actionable** (RULE 6): setiap angka mengarah ke daftar/aksi (mis. "3 RTL terlambat" → klik → daftar RTL + tombol eskalasi).
- **Aksesibilitas**: kontras WCAG AA, fokus keyboard, label form, `aria-live` untuk status sinkron & notifikasi.
- **Responsif**: S3 & S6 diuji pada viewport 360px; body tidak pernah scroll horizontal; tabel lebar → kontainer scroll sendiri.
- **Bahasa**: Indonesia (istilah domain: siklus, binaan, refleksi, RTL, umpan balik).

## Alur kritis untuk UI test (master prompt §17)

**Guru:** login → lihat jadwal → isi refleksi → lihat hasil observasi → konfirmasi umpan balik → unggah bukti RTL
**Supervisor:** login → buat siklus → perencanaan → observasi (termasuk skenario luring→sinkron) → analisis (tinjau draft AI) → umpan balik → RTL → laporan
