# Decision Log & Progress

Catatan keputusan berjalan + status implementasi per story. ADR formal ada di `architecture.md`.

## Keputusan checkpoint (2026-09-09)

| Ref | Keputusan | Sumber |
|---|---|---|
| R-01 | **Bangun Fase 3 sekarang** dengan penanda `@provisional`, perubahan additive-only setelah SLR Gate 6/7. | User checkpoint |
| R-02 | **Multi-dinas via scoping**. Hirarki Dinas(kab/kota)→Sekolah→User. Tanpa level provinsi (additive bila perlu). | User checkpoint |
| R-03 | **API internal-first** `/api/v1` memetakan Spec §8 + endpoint sync PWA. Controller = shell tipis atas Action domain. Tanpa portal dokumentasi publik di MVP. | User checkpoint |
| R-04 | **Offline: konsol Observasi (M2) + antrean bukti RTL (M5)**. Refleksi guru = online. | User checkpoint |
| — | AI default `MockAiProvider`; provider nyata hanya bila kunci API diset. | ADR-009 |
| — | Test DB = PostgreSQL `esupervisi_test` (paritas fitur, bukan SQLite). | testing.md |
| — | Larastan level `max`; `checkModelProperties` dimatikan (parser phpdoc menolak pseudo-type Larastan pada anotasi buatan tangan). | A1 |
| — | Pest 5 + PHPUnit 13 (Pest 4 tidak kompatibel Laravel 13 + PHPUnit 12 di lingkungan ini). `laravel/pao` dihapus (konflik Pest). | A1 |

## Progres implementasi

Legend: ⬜ belum · 🟡 berjalan · ✅ selesai (DoD) · ⏸️ ditunda

### PHASE 0 — Discovery & Architecture ✅
- ✅ Inspeksi repo & lingkungan, ekstraksi spesifikasi
- ✅ 10 artefak Phase 0 (`docs/`)
- ✅ Checkpoint R-01..R-04 dijawab user

### PHASE 1 — Fondasi (M13–M17) 🟡

| Story | Status | Catatan |
|---|---|---|
| A1 Skeleton & tooling | ✅ | Laravel 13 + Postgres + Pest 5 + Livewire 3 + Tailwind 4 + Larastan max + Pint strict; `composer ci` hijau; domain skeleton `app/Domain/*`; `config/ai.php`; `DomainServiceProvider`; API (Sanctum) terpasang |
| A2 Autentikasi | ⬜ | |
| A3 RBAC & Policy foundation | ⬜ | matriks `rbac.md` → test parametrik |
| A4 Struktur organisasi (dinas/sekolah) | ⬜ | |
| A5 Manajemen pengguna | ⬜ | |
| A6 Penugasan supervisor–guru | ⬜ | |
| A7 Audit log append-only | ⬜ | |
| A8 Notifikasi foundation | ⬜ | |
| A9 Konfigurasi & kebijakan | ⬜ | |
| A10 Bantuan & dukungan | ⬜ | |
| A11 Shell layout & design system | ⬜ | |
| Seed data fondasi | ⬜ | |

### PHASE 2–5
Belum dimulai. Lihat `roadmap.md`.
