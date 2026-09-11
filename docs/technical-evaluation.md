# Evaluasi Teknis Artefak

> Fase 5 E4. Melengkapi expert judgment (`docs/expert-judgment.md`) dengan
> evaluasi teknis: keamanan, pengujian, dan performa. Dijalankan pada
> `tag phase5-complete`.

---

## 1. Ringkasan tinjauan keamanan

Tinjauan keamanan dilakukan **per fase** (bagian 5 tiap laporan fase). Status
konsolidasi terhadap risiko `docs/risk-register.md`:

| ID | Risiko | Mitigasi terpasang | Status |
|---|---|---|---|
| T-01 | Kehilangan/duplikasi data observasi luring | UUID klien = PK (idempoten); unik `(observation_id,item_key)`; optimistic lock `version`; deteksi konflik + resolusi manual; diuji `ObservationSyncTest` (API) + `tests/Browser/ObservationOfflineSyncTest.php` (Chromium sungguhan — luring→online tanpa duplikat) | MITIGATED |
| T-02 | Bias/kesalahan AI memengaruhi penilaian guru | Semua keluaran `draft`; `App\Domain\Ai\Providers` tanpa akses DB (arch test); tak memicu transisi; gate berlapis (`FinalizeAnalysis`, `PostFeedbackMessage`, state machine) | MITIGATED |
| T-03 | Kebocoran data lintas peran (IDOR, cross-school/dinas) | Default deny; Policy + global scope `visibleTo`; route-model-binding; suite `tests/Feature/Security`; audit `authorization.denied` | MITIGATED |
| T-04 | Kepatuhan UU 27/2022 (PDP) | Header keamanan respons (CSP nonce+hash, HSTS, X-Frame-Options, Referrer/Permissions-Policy) via `SecureHeaders` middleware (ADR-016, diuji); `FORCE_HTTPS`/`TRUSTED_PROXIES`/`SESSION_SECURE_COOKIE` siap pakai untuk produksi (diuji); enkripsi at-rest berkas observasi (`deployment.md`, operasional); retensi = arsip bukan hapus; audit menyeluruh; consent guru wajib untuk publikasi praktik baik; 360° anonim di atas ambang | OPEN (review hukum + basis pemrosesan — butuh pihak berkompeten, bukan kode) |
| T-05 | Rework karena SLR mengubah prioritas Fase 3–4 | `@provisional` + additive-only + titik revisi Gate 6/7 didokumentasikan | ACCEPTED |
| T-06 | Batas domain modular monolith luntur | Namespace convention + Larastan + **11 arch test** (`tests/Arch/LayerTest.php`) melarang import lintas domain terlarang | MITIGATED |
| T-07 | Media besar 3T tak terunggah | Metadata dulu, file antre; UI jujur soal status | ACCEPTED (known limitation) |
| T-08 | Solution-first bias merusak kredibilitas DSR | `docs/dsr-artefak.md` — problem identification dari evidence map Artikel 1; iterasi versi lama→v2 sebagai design cycle sah | MITIGATED |
| T-09 | Performa dasbor & laporan agregat volume besar | Index terencana; `report_snapshots` materialisasi; diuji `tests/Feature/Performance/*` pada ~200 siklus | MITIGATED |
| T-10 | Scope creep 18 modul | Roadmap berfase + exit criteria + checkpoint RULE 10 (R-01..R-05, F4-01..F4-04) | MITIGATED |
| T-11 | PHP 8.4 vs "8.3+" | `composer.json` `php: ^8.3`; berjalan di 8.4 | ACCEPTED |
| T-12 | Kompleksitas Livewire konsol observasi luring | Modul Alpine/JS mandiri + API (ADR-006) | MITIGATED |
| T-13 | Backup & DR belum dirancang | `deployment.md`: `pg_dump` terjadwal + retensi + uji restore | OPEN (operasional) |

### Kontrol keamanan yang diuji otomatis (`tests/Feature/Security`, `tests/Feature/Rbac`)

- Guru A tidak dapat mengakses siklus guru B → 403 + audit.
- Pengawas tidak dapat mengakses guru di luar `supervisor_assignments` meski satu dinas.
- Admin Dinas X tidak dapat melihat data Dinas Y.
- `audit_logs` immutable — tidak ada route/aksi update/delete (`AuditLogImmutabilityTest`).
- Gate terdaftar untuk **setiap** `Permission`; pengguna nonaktif ditolak seluruh permission.
- Matriks `rbac.md` (guru/supervisor/admin dinas/admin sistem/ahli) diverifikasi parametrik — 46+ sel.
- Fase 4/5: kurasi praktik baik lintas dinas ditolak; non-peserta kalibrasi ditolak;
  supervisor tak bisa membuat sesi kalibrasi/panel evaluasi; ahli tak terdaftar
  tak bisa mengirim penilaian.

### Sisa pekerjaan keamanan (bukan blok evaluasi ahli)

- Review hukum PDP formal + dokumen basis pemrosesan (checklist `deployment.md`).
- ~~Secure headers (CSP, HSTS)~~ — **selesai**: `SecureHeaders` middleware +
  `config/security.php` + `tests/Feature/Security/SecureHeadersTest.php` (ADR-016).
  Rate-limit produksi (naikkan batas login/sync) tetap konfigurasi deployment.
- ~~Uji unggah berkas berbahaya (`.php`/`.svg`)~~ — **selesai**:
  `tests/Feature/Security/MediaUploadSecurityTest.php` (8 tes) membuktikan
  `mimes:` Laravel mendeteksi dari konten asli (fileinfo), bukan ekstensi
  klien — `.php` diblokir eksplisit, `.svg` tak di-whitelist, PHP berkedok
  `.jpg` gagal deteksi konten. Ditambah lapis kedua: `tipe` (video/audio/
  foto/dokumen) harus cocok kategori ekstensi hasil deteksi konten
  (`UploadObservationMediaRequest::withValidator`), dan `original_name`
  disanitasi (`basename()` + lucuti karakter kontrol) sebelum disimpan.
- ~~Belum ada `tests/Browser` otomatis~~ — **selesai**:
  `tests/Browser/ObservationOfflineSyncTest.php` (Pest\Browser + Playwright
  Chromium sungguhan; lihat `docs/testing.md` §Browser). Opt-in
  (`composer test:browser`), tidak masuk `composer ci` (butuh Playwright
  terpasang, ~280 MB binari Chromium — tak deterministik untuk gate wajib).

## 2. Inventaris pengujian

Line coverage **tidak tersedia** (tanpa Xdebug/PCOV di lingkungan). Sebagai
gantinya, inventaris per kategori — gate `composer ci`:

| Kategori | Berkas | Tes | Cakupan kunci |
|---|---|---|---|
| Unit | 9 | 89 | state machine + guard, `SchemaDrivenScorer`, `InstrumentSchema`, `RolePermissionMap` (matriks), `PolicySettings`, `CalibrationStats`, `PkbMatcher`, `ExpertJudgmentStats`, SUS |
| Feature | ~24 | 116 | auth, siklus end-to-end, sync idempoten/konflik, AI human-in-the-loop, pasca-observasi, program M7, PKB M9, praktik baik M10, 360° M11, kalibrasi M12, evaluasi ahli Fase 5, render layar Fase 4–5, performa |
| Security | 5 | — (di dalam Feature) | IDOR, cross-dinas/school, audit immutability, privilege escalation, header keamanan respons (CSP nonce+hash, HSTS, X-Frame-Options, dst.), **unggah berkas berbahaya (`.php`/`.svg`/spoofed extension/oversize/tipe-mismatch)** |
| Architecture | 1 | 11 | larangan import lintas domain, AI tanpa DB, state machine satu penulis, isolasi domain Fase 4–5, kemurnian helper statistik |
| Performance | 1 | 3 | dasbor & laporan agregat pada ~200 siklus |
| Static | — | — | Pint (strict types) + Larastan level 8 "No errors" |
| Browser *(opt-in, di luar `composer ci`)* | 1 | 10 | luring→online: deteksi offline (`navigator.onLine`), antre IndexedDB, sinkron otomatis saat online, tanpa duplikasi server — Chromium sungguhan via Pest\Browser + Playwright |

**Total (`composer ci`): 249 tes / 670 assertions, hijau.** (termasuk ekspor laporan server-side dompdf/OpenSpout + header keamanan respons + hardening unggah berkas pasca-Fase 5.) Ditambah 1 tes browser opt-in (`composer test:browser`, butuh Playwright — lihat `docs/testing.md`).

Perintah:
```bash
composer ci      # pint --test + phpstan level 8 + pest (semua suite)
composer test    # pest saja
```

## 3. Hasil uji performa (`tests/Feature/Performance/`)

Diukur pada PostgreSQL 16 lokal, PHP 8.4, dataset **200 siklus** untuk satu
pengawas dalam satu dinas (menyentuh seluruh status).

| Jalur | Query | Wall-clock (incl. render Livewire/test harness) | Ambang uji |
|---|---|---|---|
| Dasbor supervisor | < 25 | ~0.5 s | query < 25, waktu < 1.5 s |
| Laporan agregat dinas (`BuildAggregateReport`) | < 15 | ~0.06 s | query < 15 |

Tidak ada N+1 pada kedua jalur. Index `supervision_cycles` tersedia untuk
`(supervisor_id, status)`, `(guru_id, status)`, `(dinas_id, tahun_ajaran)`,
`(status)`, `(program_id)` (lihat `docs/database.md`).

## 4. Prosedur uji beban manual (lingkungan target)

Untuk lingkungan target 3T (VPS tunggal). Tidak dijalankan di `composer ci`
(hasil non-deterministik).

```bash
# Siapkan data volume
php artisan migrate:fresh --seed

# Uji rute baca kunci (butuh cookie sesi valid — login dulu, salin cookie)
wrk -t2 -c20 -d30s -H "Cookie: <session-cookie>" http://e-supervisi.test/dashboard
wrk -t2 -c20 -d30s -H "Cookie: <session-cookie>" http://e-supervisi.test/reports

# Endpoint sync (bearer token PWA)
wrk -t2 -c10 -d30s -H "Authorization: Bearer <token>" http://e-supervisi.test/api/v1/sync/bootstrap
```

Target awal (indikatif, bukan SLA): p95 < 800 ms pada 20 koneksi paralel untuk
rute baca; queue worker mampu memproses job AI/notifikasi dalam < 10 s pada
beban normal. Sesuaikan `queue:work` & opcache; naikkan ke Redis bila p95 memburuk.

## 5. Kesimpulan evaluasi teknis

Artefak **siap dinilai ahli**: fungsi end-to-end lengkap dan teruji, kontrol
keamanan inti termitigasi & diuji otomatis (header keamanan respons CSP/HSTS
via `SecureHeaders` ADR-016; unggah berkas divalidasi & diuji terhadap
skenario berbahaya), performa jalur baca dalam batas pada volume realistis.
Sisa pekerjaan (review hukum PDP, enkripsi at-rest + backup deployment,
browser test, uji beban lapangan) bersifat **operasional/pra-go-live**, bukan
prasyarat evaluasi ahli Fase 5.
