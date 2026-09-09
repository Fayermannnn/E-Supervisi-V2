# CLAUDE.md — Panduan Agen untuk Repo Ini

Sistem **E-Supervisi Klinis Pendidikan v2.0**. Baca `README.md` dan `docs/` sebelum bekerja.
`Spesifikasi_E-Supervisi_v2_Evidence-Informed.docx` = **source of truth**. Sistem versi lama **deprecated**.

## Aturan wajib

1. **Modular monolith.** Kode domain di `app/Domain/<Domain>/`. Jangan import lintas domain yang dilarang `docs/domain-map.md`. Model Eloquent di `app/Models` (docblock `@domain`).
2. **Modul Provisional** (M3–M6, M18): entitas & endpoint ditandai `@provisional`. Setelah dikunci (SLR Gate 6/7) hanya perubahan **additive**. Lihat `docs/risk-register.md`.
3. **State machine** siklus: hanya lewat `App\Domain\Supervision\StateMachine\CycleStateMachine`. 10 status, tidak ada tambahan. `docs/state-machine.md`.
4. **RBAC default deny.** Setiap aksi lewat Policy; setiap query siklus lewat global scope. `docs/rbac.md` = kontrak (diuji).
5. **AI** (`app/Domain/Ai`): tidak akses DB langsung, output selalu `draft`, tidak memicu transisi status. Default `MockAiProvider`.
6. **Audit log append-only.** Tidak ada route/aksi update/delete `audit_logs`.
7. **Migrasi non-destruktif.** Drop kolom/tabel berdata butuh konfirmasi user eksplisit (RULE 8).
8. **DoD per modul** (`docs/backlog.md`): migration + model + FormRequest + Policy + logic + Livewire UI + error handling + audit + notifikasi + test (unit/feature/security) + responsif + dokumentasi. "UI jadi" ≠ "selesai".
9. Setiap fitur harus punya dasar requirement (RULE 3). Jika ambiguity mempengaruhi arsitektur → berhenti, jelaskan opsi (RULE 10).

## Stack

PHP 8.4 (Herd) · Laravel 13 · PostgreSQL 16 · Livewire 3 · Tailwind 4 · Pest 5 · Larastan 8 · Pint (strict types).

## Perintah

```bash
composer ci          # pint --test + phpstan + pest   (gate sebelum commit)
composer test        # pest saja
composer stan        # phpstan (level 8)
composer lint        # pint (perbaiki format)
php artisan migrate:fresh --seed
npm run dev
```

- Test DB: `esupervisi_test` (PostgreSQL, bukan SQLite — paritas jsonb/enum).
- `head`/`tail` di shell zsh milik user rusak (ter-alias). Gunakan tool `Read`, atau `sed -n`.
- Working directory bash mengikuti `cd` terakhir — selalu `cd "/Users/firmansyah/CLAUDE CODE"` di awal.

## Commit

`type(domain): ringkas` — mis. `feat(observation): implement offline sync queue`.
Akhiri commit message dengan `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.
Satu fase selesai → tag `phaseN-complete` + laporan 7-bagian (`docs/roadmap.md` §25).

## Status

Lihat tabel fase di `README.md`. Fase aktif & progres di `docs/DECISIONS.md`.
