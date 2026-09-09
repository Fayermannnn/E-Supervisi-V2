# RBAC — Aktor, Matriks Hak Akses, Scoping Data

Sumber: Spec §4, §10. Prinsip: **default deny**. Setiap aksi domain melewati `Policy`. Setiap query data siklus melewati **global scope** berbasis peran.

## Aktor (Spec §4)

| Aktor | Peran sistem | Ringkas |
|---|---|---|
| **Guru** | `guru` | Subjek supervisi. Hanya data siklusnya sendiri. |
| **Supervisor** | `supervisor` (`supervisor_type`: `kepala_sekolah` \| `pengawas`) | Pelaksana 6 tahap untuk **guru binaan**. Scope via `supervisor_assignments` + batas sekolah/dinas. |
| **Admin Dinas** | `admin_dinas` (di-scope ke `dinas_id`) | Pemantauan **agregat** lintas sekolah dalam dinasnya; kelola bank indikator; ekspor laporan kebijakan. **Bukan** akses data individual tanpa dasar kebijakan. |
| **Admin Sistem** | `admin_sistem` | Teknis: manajemen user, konfigurasi, audit log, backup. **Tidak** mengakses konten pedagogis siklus. |
| **Asisten AI** | agen sistem (bukan user) | Baca data observasi utk draft; **tidak punya hak tulis final**. Berjalan atas nama supervisor yang meminta, hasil selalu `draft`. |

## Matriks hak akses (aksi × aktor)

Legend: ✅ boleh · 🔶 boleh (terbatas scope) · ➖ tidak · 👁 read-only

| Aksi | Guru | Supervisor | Admin Dinas | Admin Sistem | AI |
|---|---|---|---|---|---|
| Login / kelola profil sendiri | ✅ | ✅ | ✅ | ✅ | ➖ |
| CRUD user, reset password | ➖ | ➖ | ➖ | ✅ | ➖ |
| Kelola struktur organisasi (dinas/sekolah) | ➖ | ➖ | 🔶 (sekolah dlm dinas) | ✅ | ➖ |
| Kelola `supervisor_assignments` | ➖ | ➖ | 🔶 | ✅ | ➖ |
| Buat siklus supervisi | ➖ | 🔶 (guru binaan) | ➖ | ➖ | ➖ |
| Lihat siklus | 🔶 (miliknya) | 🔶 (binaan) | 👁 agregat + detail sesuai kebijakan | ➖ | 👁 (konteks draft) |
| Kunci jadwal & fokus observasi | ➖ | 🔶 | ➖ | ➖ | ➖ |
| Isi refleksi pra-observasi | 🔶 (miliknya) | ➖ | ➖ | ➖ | ➖ |
| Setujui kesepakatan pra-observasi | 🔶 | 🔶 | ➖ | ➖ | ➖ |
| Lakukan observasi / isi instrumen | ➖ | 🔶 | ➖ | ➖ | ➖ |
| Sinkron data observasi luring | ➖ | 🔶 (device token) | ➖ | ➖ | ➖ |
| Lihat hasil observasi | 🔶 (miliknya, setelah final) | 🔶 | 👁 agregat | ➖ | 👁 |
| Hitung skor / buat analisis | ➖ | 🔶 | ➖ | ➖ | ➖ |
| Minta draft analisis AI | ➖ | 🔶 | ➖ | ➖ | (menghasilkan) |
| Kunci analisis final | ➖ | 🔶 | ➖ | ➖ | ➖ (dilarang) |
| Buat/rekam sesi umpan balik | ➖ | 🔶 | ➖ | ➖ | ➖ |
| Minta draft saran umpan balik AI | ➖ | 🔶 | ➖ | ➖ | (menghasilkan) |
| Kirim umpan balik final ke guru | ➖ | 🔶 | ➖ | ➖ | ➖ (dilarang) |
| Konfirmasi penerimaan umpan balik | 🔶 (miliknya) | ➖ | ➖ | ➖ | ➖ |
| Buat RTL | ➖ | 🔶 | ➖ | ➖ | ➖ |
| Update status/checklist RTL | 🔶 (miliknya) | 🔶 | ➖ | ➖ | ➖ |
| Unggah bukti pelaksanaan RTL | 🔶 (miliknya) | ➖ | ➖ | ➖ | ➖ |
| Eskalasi RTL terlambat | ➖ | 👁 (menerima) | 👁 (agregat) | ➖ | 🔶 (menandai saja, R§11-3) |
| Susun laporan siklus | ➖ | 🔶 | ➖ | ➖ | ➖ |
| Lihat laporan agregat lintas sekolah | ➖ | ➖ | 🔶 (dinasnya) | ➖ | 🔶 (deteksi anomali, tandai saja) |
| Ekspor laporan (PDF/XLSX/CSV) | ➖ | 🔶 (siklusnya) | 🔶 (agregat dinas) | ➖ | ➖ |
| Kelola bank indikator/instrumen | ➖ | ➖ | 🔶 (dinasnya + global read) | ✅ | ➖ |
| Lihat audit log | ➖ | 👁 (siklus binaan, terbatas) | 👁 (dinas, terbatas) | ✅ | ➖ |
| Hapus/ubah audit log | ➖ | ➖ | ➖ | ➖ | ➖ (tidak ada jalur kode) |
| Konfigurasi sistem / kebijakan | ➖ | ➖ | 🔶 (kebijakan dinas) | ✅ (teknis) | ➖ |
| Batalkan siklus (alasan tercatat) | ➖ | 🔶 | ➖ | ➖ | ➖ |

## Scoping data (global query scopes)

| Peran | Scope `supervision_cycles` |
|---|---|
| `guru` | `WHERE guru_id = auth()->id()` **dan** status ≥ tahap yang boleh dilihat guru (mis. hasil observasi hanya setelah `status >= 2` & observasi `final`) |
| `supervisor` | `WHERE supervisor_id = auth()->id()` **atau** `guru_id IN (assignments aktif)` — dibatasi `sekolah_id` (kepala_sekolah) atau `dinas_id` (pengawas) |
| `admin_dinas` | `WHERE dinas_id = <dinas peran>` — **default hanya kolom agregat**; detail per-siklus butuh flag kebijakan `policy_settings['dinas.can_view_cycle_detail']` |
| `admin_sistem` | tanpa scope konten, **tetapi** field pedagogis (catatan observasi, umpan balik, refleksi) disembunyikan di UI admin sistem |
| AI | konteks dibangun eksplisit oleh Action pemanggil (supervisor), bukan query bebas |

## Aturan tambahan

1. **IDOR**: setiap route `{cycle}`, `{observation}`, `{follow_up}` di-resolve via route-model-binding + `Policy::view`. Tidak ada endpoint yang menerima `id` tanpa cek kepemilikan.
2. **Cross-school**: `pengawas` tidak bisa mengakses guru di luar `supervisor_assignments`-nya meski satu dinas. Diuji di `tests/Feature/Security`.
3. **Privilege escalation**: perubahan `role_user` hanya lewat Action `AssignRole` milik `admin_sistem`, dengan audit + tidak bisa menetapkan `admin_sistem` ke diri sendiri tanpa approver kedua (fase lanjut).
4. **AI boundary**: layer `Ai` di-inject `AiContextBuilder` yang hanya menerima entitas yang sudah lolos Policy pemanggil; `AiProvider` tidak punya akses DB.
5. **Token PWA**: scope minimal (`observation:sync`, `follow-up:evidence`), kedaluwarsa, dapat dicabut per device oleh user & admin.
6. Semua kegagalan otorisasi → HTTP 403 + baris `audit_logs` (`action = authorization.denied`).
