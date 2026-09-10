# Skenario Demo End-to-End

> Untuk demonstrasi artefak (DSR Artikel 3, Fase 5 E1). Semua data adalah dummy
> (nama fiktif, NIP pola `9999…`). `AI_PROVIDER=mock` — tidak perlu kunci API.

## Persiapan

```bash
cd "/Users/firmansyah/CLAUDE CODE"
php artisan migrate:fresh --seed
npm run dev            # atau: npm run build
```

Server: `http://e-supervisi.test` (Herd) atau `php artisan serve`.

## Akun demo (kata sandi semua: `password`)

| Peran | Email | Untuk mendemokan |
|---|---|---|
| Admin Sistem | `admin.sistem@esupervisi.test` | Manajemen pengguna, kebijakan, audit, **panel evaluasi ahli** |
| Admin Dinas | `admin.dinas@esupervisi.test` | Laporan agregat, katalog PKB, kurasi praktik baik, kalibrasi, akuntabilitas dinas |
| Supervisor (kepsek) | `kepsek.mahulu.1@esupervisi.test` | Siklus penuh, program tahunan, nominasi praktik baik |
| Guru | `guru.mahulu.1.0@esupervisi.test` | Refleksi, konfirmasi umpan balik, bukti RTL, rekomendasi PKB, penilaian 360° |
| Pengawas | `pengawas.mahulu@esupervisi.test` | Siklus lintas sekolah, peserta kalibrasi |
| Ahli Evaluator | `ahli.mp1@esupervisi.test` / `ahli.si1@esupervisi.test` | Mengisi penilaian ahli (CVR/Aiken/SUS) |

Seeder membuat **8 siklus** melintasi seluruh status (Draf → Tindak Lanjut Terlambat),
1 program tahunan (3 siklus DRAFT), 4 item katalog PKB, 3 penilaian 360°, 1 sesi
kalibrasi selesai, dan 1 panel evaluasi ahli selesai.

---

## Alur 1 — Siklus supervisi penuh (Supervisor + Guru)

1. **Supervisor** → `Siklus Supervisi` → pilih siklus berstatus *Draf* → **Perencanaan**:
   isi fokus, pilih instrumen (Format B contoh), jadwal → setujui sebagai supervisor.
2. **Guru** → siklus yang sama → setujui kesepakatan + isi refleksi pra-observasi.
   Siklus otomatis → **Terjadwal**, guru dapat notifikasi.
3. **Supervisor** → **Buka konsol observasi**:
   - isi beberapa item, tutup tab → data tersimpan di IndexedDB;
   - **matikan jaringan** (DevTools → Offline), isi item lain, indikator menampilkan
     "Tersimpan lokal · Menunggu sinkron";
   - **nyalakan jaringan** → outbox ter-flush, indikator "Tersinkron", **tanpa duplikat**;
   - **Finalkan observasi** → siklus → **Observasi Selesai**.
4. **Analisis**: skor dihitung deterministik dari `scoring_config`; klik
   **Minta draf AI** → banner "DRAF/SARAN AI"; klik **Tinjau & sunting**, edit
   ringkasan, **Kunci analisis final** (wajib manusia) → **Analisis Selesai**.
5. **Ruang umpan balik**: supervisor mengirim pesan observasi/kesepakatan; guru
   menanggapi lalu **konfirmasi penerimaan** → **Umpan Balik Diberikan**.
6. **Pelacak Tindak Lanjut**: buat RTL dengan tenggat + butir → **Tindak Lanjut Berjalan**.
   Jalankan `php artisan esupervisi:detect-overdue-followups` dengan tenggat lampau →
   siklus → **Tindak Lanjut Terlambat** + kartu merah + eskalasi notifikasi.
   Guru unggah bukti + tandai butir selesai → kembali **Berjalan**.
7. **Laporan siklus** → **Susun laporan** → **Dilaporkan**; cetak via browser (PDF).
8. **Admin Dinas** → **Pelaporan Agregat** → lihat sebaran status lintas sekolah,
   filter wilayah/jenjang, anomali (label saja).

## Alur 2 — Program supervisi tahunan (M7)

1. **Supervisor** → `Program Tahunan` → buka *Program Supervisi Klinis 2026/2027*.
2. Lihat 3 target dengan siklus DRAFT sudah tergenerate. Centang guru binaan lain →
   **Simpan daftar target** → **Generate N siklus** (idempoten).
3. Buka salah satu siklus DRAFT → lanjutkan lewat Alur 1.

## Alur 3 — PKB & praktik baik (M9/M10)

1. **Guru/Supervisor** → siklus yang sudah dianalisis → **PKB & Praktik Baik**.
2. **Supervisor** → **Susun ulang rekomendasi** → rekomendasi muncul dari area
   pengembangan (tanda "Pola berulang" bila berulang lintas siklus).
3. **Guru** → **Pilih** rekomendasi → status *dipilih* → nanti *selesai*.
4. Pada siklus **Dilaporkan** berskor tinggi: **Supervisor** → **Nominasikan** praktik baik.
5. **Guru** → **Setujui** (persetujuan wajib, UU PDP).
6. **Admin Dinas** → `Praktik Baik` → antrean kurasi → **Terbitkan** → tampil dinas-wide.

## Alur 4 — Akuntabilitas 360° (M11)

1. **Guru** → siklus di tahap *Umpan Balik Diberikan* / *Tindak Lanjut* → **Penilaian 360°**
   → isi 5 dimensi + komentar → **Kirim** (dapat disunting s/d siklus *Dilaporkan*).
2. **Supervisor** → `Akuntabilitas 360°` → lihat rata-rata dimensi (muncul bila
   responden ≥ 3; di bawah itu "data belum cukup").
3. **Admin Dinas** → `Akuntabilitas 360°` → agregat lintas sekolah + per supervisor.

## Alur 5 — Kalibrasi antar-penilai (M12)

1. **Admin Dinas** → `Kalibrasi Penilai` → buka sesi *Kalibrasi Format B* (sudah selesai).
2. Lihat hasil: persen kesepakatan, Fleiss' κ, deviasi absolut, tabel per butir.
3. Untuk sesi baru: **Sesi baru** → pilih instrumen + tautan artefak → **Tambah penilai**
   (≥2 supervisor) → tiap penilai `Kirim skor` → **Tutup sesi & hitung**.

## Alur 6 — Evaluasi ahli (Fase 5)

1. **Admin Sistem** → `Evaluasi Ahli` → buka panel *Validasi Artefak E-Supervisi…* (selesai).
2. Lihat hasil: CVI, Aiken's V rata-rata, SUS rata-rata + interpretasi, tabel per aspek,
   ringkasan per rumpun ahli.
3. Alur baru: **Panel baru** → **Tugaskan** ahli (email akun ahli + rumpun) →
   **Ahli** login → `Evaluasi Ahli` → isi relevansi + kualitas + 10 butir SUS →
   **Kirim** → **Admin Sistem** → **Tutup panel & hitung**.

---

## Perintah terjadwal (untuk demo eskalasi/arsip)

```bash
php artisan esupervisi:dispatch-reminders          # pengingat RTL
php artisan esupervisi:detect-overdue-followups    # deteksi RTL terlambat + eskalasi
php artisan esupervisi:archive-cycles              # arsip siklus Dilaporkan
```
