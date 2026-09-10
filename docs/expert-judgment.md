# Instrumen Expert Judgment & Kuesioner Usability

> Fase 5 E2. Instrumen validasi artefak Sistem E-Supervisi oleh panel ahli
> (DSR Artikel 3, Spec §14). Definisi instrumen ada di kode
> (`app/Domain/Evaluation/`) dan dijalankan lewat modul **Evaluasi Ahli**
> (`/evaluation`). Dokumen ini adalah rujukan isi + cara hitung.

---

## 1. Komposisi panel

- **Minimal 2 rumpun ahli** (Spec §14; konsisten dgn validasi Artikel 2):
  - `manajemen_pendidikan` — ahli manajemen/administrasi/supervisi pendidikan
  - `sistem_informasi` — ahli rekayasa perangkat lunak / sistem informasi
  - `lainnya` — bila relevan
- Jumlah panelis disarankan **5–10** (CVR Lawshe bermakna mulai N=5).
- Tiap ahli diberi akun peran `ahli` oleh Admin Sistem, lalu ditugaskan ke panel.

## 2. Bagian A — Relevansi aspek (CVR / CVI, Lawshe 1975)

Tiap ahli menilai setiap aspek: **Esensial** / **Berguna tetapi tidak esensial** /
**Tidak diperlukan**.

| Kunci | Aspek |
|---|---|
| `kelengkapan_tahap` | Sistem mendukung keenam tahap siklus supervisi klinis secara utuh. |
| `kesesuaian_teori` | Alur & status sistem konsisten dengan kerangka Acheson & Gall (1997) / kode Artikel 1. |
| `human_in_the_loop` | Mekanisme human-in-the-loop pada fitur AI memadai. |
| `keamanan_privasi` | RBAC & pelindungan data pribadi (UU 27/2022) memadai untuk data guru. |
| `dukungan_luring` | Dukungan mode luring pada observasi & bukti RTL sesuai konteks 3T. |
| `tindak_lanjut` | Fitur tindak lanjut (RTL, pengingat, eskalasi) memperkuat mata rantai lemah. |
| `pelaporan_data` | Pelaporan berbasis data (per siklus & agregat) berguna untuk keputusan dinas. |
| `pengembangan_profesional` | Katalog PKB, praktik baik, akuntabilitas 360°, kalibrasi relevan untuk mutu supervisi. |

**Rumus.** Untuk tiap aspek: `CVR = (n_e − N/2) / (N/2)`, dengan `n_e` = jumlah
panelis yang menilai "Esensial", `N` = total panelis. Rentang −1..+1.
**CVI** = rata-rata CVR seluruh aspek yang dipertahankan.

**Nilai kritis CVR** (satu sisi, p = .05) — aspek diterima bila `CVR ≥` nilai berikut:

| N | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12 | 13 | 14 | 15 | 20 | 25 | 30 | 40 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| CVR kritis | .99 | .99 | .99 | .85 | .78 | .62 | .59 | .56 | .54 | .51 | .49 | .42 | .37 | .33 | .29 |

(Tabel di `App\Domain\Evaluation\ExpertJudgmentStats::CVR_CRITICAL`; untuk N di
antara nilai tabel, dipakai nilai kritis N terdekat yang ≤ N — konservatif.)

## 3. Bagian B — Kualitas aspek (Aiken's V, 1985)

Tiap ahli menilai kualitas realisasi setiap aspek pada **skala 1–5**
(1 = sangat kurang, 5 = sangat baik).

**Rumus.** `V = Σ s / (n · (c − 1))`, dengan `s = r − l` (`r` = skor, `l` = 1),
`c` = 5 (jumlah kategori), `n` = jumlah penilai. Rentang 0..1.

**Interpretasi (rujukan):** V < 0.4 rendah · 0.4–0.8 sedang · > 0.8 tinggi.
Untuk keputusan, gunakan tabel nilai kritis Aiken sesuai `n` dan `c`.

## 4. Bagian C — Usability (System Usability Scale, Brooke 1996)

Sepuluh pernyataan, skala 1 (sangat tidak setuju) – 5 (sangat setuju). Butir
ganjil positif, genap negatif (`App\Domain\Evaluation\UsabilityQuestionnaire`).

1. Saya rasa saya ingin sering menggunakan sistem ini.
2. Saya merasa sistem ini terlalu rumit.
3. Saya rasa sistem ini mudah digunakan.
4. Saya rasa saya butuh bantuan teknis untuk dapat menggunakan sistem ini.
5. Saya rasa fungsi-fungsi dalam sistem ini terpadu dengan baik.
6. Saya rasa terlalu banyak ketidakkonsistenan dalam sistem ini.
7. Saya rasa kebanyakan orang akan cepat memahami cara memakai sistem ini.
8. Saya rasa sistem ini sangat merepotkan untuk digunakan.
9. Saya merasa sangat percaya diri saat menggunakan sistem ini.
10. Saya perlu belajar banyak hal dulu sebelum bisa menggunakan sistem ini.

**Skor per responden (0–100):**
`( Σ(butir ganjil: skor − 1) + Σ(butir genap: 5 − skor) ) × 2.5`

**Interpretasi:** ≥ 85.5 A (sangat baik) · ≥ 72.6 B (baik) · ≥ 62.7 C
(di atas rata-rata) · ≥ 51.7 D (di bawah rata-rata) · < 51.7 F (buruk).
Rata-rata industri ≈ 68.

## 5. Alur pelaksanaan di sistem

1. **Admin Sistem** membuat panel (`/evaluation`) — mencatat versi artefak yang dinilai.
2. Menugaskan tiap ahli (email + rumpun + afiliasi). Peran `ahli` diberikan otomatis.
3. **Ahli** login → `/evaluation/{panel}/review` → mengisi Bagian A + B + C + catatan.
   Dapat disunting selama panel `berjalan`.
4. **Admin Sistem** menutup panel → sistem menghitung & menyimpan snapshot:
   CVR & signifikansi per aspek, CVI, Aiken's V per aspek + rata-rata, SUS per
   ahli + rata-rata + interpretasi, ringkasan per rumpun.
5. Hasil diekspor manual (salin dari layar hasil / `evaluation_panels.stats` jsonb)
   ke manuskrip Artikel 3 bagian *Evaluation*.

## 6. Ambang keputusan (dilaporkan; keputusan pada peneliti)

| Metrik | Memadai | Kuat |
|---|---|---|
| CVR per aspek | ≥ nilai kritis Lawshe (tabel §2) | — |
| CVI | ≥ 0.6 | ≥ 0.8 |
| Aiken's V (rata-rata) | ≥ 0.6 | ≥ 0.8 |
| SUS (rata-rata) | ≥ 68 | ≥ 72.6 (B) |

## 7. Catatan validitas

- Instrumen ini **belum** melalui uji keterbacaan pada panel nyata — dipakai apa
  adanya untuk expert judgment Fase 5; revisi redaksional aspek diperbolehkan
  sebelum panel dijalankan (ubah `ExpertJudgmentInstrument::aspects()`).
- Perhitungan bersifat deterministik & teruji (`tests/Unit/ExpertJudgmentStatsTest.php`)
  sehingga hasil dapat direproduksi dari data mentah.
