# Route Map — Web (Livewire) & API

Status: **DRAFT.** Endpoint modul Provisional ditandai `@provisional` (boleh berubah setelah Gate 6/7 SLR).
Konvensi API: prefix `/api/v1`, auth Sanctum, envelope `{ data, meta, errors }`, `FormRequest` + `Policy` di setiap endpoint.

## 1. Rute Web (Blade + Livewire) — `routes/web.php`

### Publik / Auth (Fase 1)
| Path | Komponen | Peran |
|---|---|---|
| `GET /login`, `POST /login` | Auth | tamu |
| `POST /logout` | Auth | semua |
| `GET /forgot-password`, `POST ...` | Auth | tamu |
| `GET /profile` | `Profile\Edit` | semua |

### Dashboard (Fase 1–2)
| Path | Komponen | Peran |
|---|---|---|
| `GET /` | redirect per peran | semua |
| `GET /dashboard` | `Dashboard\SupervisorDashboard` | supervisor |
| `GET /dashboard` | `Dashboard\TeacherDashboard` | guru |
| `GET /dashboard` | `Dashboard\DinasDashboard` | admin_dinas |
| `GET /admin` | `Admin\SystemDashboard` | admin_sistem |

### Administrasi (Fase 1)
| Path | Komponen | Peran |
|---|---|---|
| `GET /admin/users` `.../create` `.../{user}/edit` | `Admin\Users\*` | admin_sistem |
| `GET /admin/organizations` (dinas/sekolah) | `Admin\Organizations\*` | admin_sistem, admin_dinas(🔶) |
| `GET /admin/assignments` | `Admin\SupervisorAssignments\*` | admin_sistem, admin_dinas |
| `GET /admin/policies` | `Admin\Policies\Index` | admin_sistem, admin_dinas |
| `GET /admin/audit-logs` | `Admin\AuditLog\Index` | admin_sistem, admin_dinas(🔶) |
| `GET /help`, `GET /help/{article}` | `Support\*` | semua |

### Siklus Supervisi (Fase 2 inti; tahap pasca = Fase 3)
| Path | Komponen | Peran | Fase |
|---|---|---|---|
| `GET /cycles` | `Cycles\Index` | supervisor, guru(🔶), admin_dinas(👁) | 2 |
| `GET /cycles/create` | `Cycles\Create` | supervisor | 2 |
| `GET /cycles/{cycle}` | `Cycles\Show` (tab per tahap) | sesuai scope | 2 |
| `GET /cycles/{cycle}/planning` | `Planning\Editor` | supervisor, guru(refleksi) | 2 |
| `GET /cycles/{cycle}/observe` | `Observation\Console` (offline-capable) | supervisor | 2 |
| `GET /cycles/{cycle}/analysis` | `Analysis\Workspace` | supervisor | 3 `@provisional` |
| `GET /cycles/{cycle}/feedback` | `Feedback\Room` | supervisor, guru | 3 `@provisional` |
| `GET /cycles/{cycle}/follow-up` | `FollowUp\Tracker` | supervisor, guru | 3 `@provisional` |
| `GET /cycles/{cycle}/report` | `Reporting\CycleReport` | supervisor, admin_dinas | 3 `@provisional` |

### Instrumen (Fase 2)
| `GET /instruments` `.../create` `.../{instrument}/versions` | `Instruments\*` | admin_dinas, admin_sistem | 2 |

### Laporan (Fase 3 `@provisional`)
| `GET /reports` | `Reporting\Index` | supervisor, admin_dinas |
| `GET /reports/aggregate` | `Reporting\Aggregate` (filter wilayah/periode) | admin_dinas |

### Program tahunan / PKB / Akuntabilitas (Fase 4)
| `GET /programs`, `/pkb`, `/best-practices`, `/accountability/*`, `/calibration/*` | Fase 4 |

---

## 2. API — `routes/api.php` (prefix `/api/v1`)

### 2a. Endpoint inti siklus (memetakan Spec §8)

| Method | Endpoint | Fungsi / Tahap | Akses | Fase |
|---|---|---|---|---|
| `POST` | `/cycles` | Buat siklus (Perencanaan) | supervisor | 2 |
| `GET` | `/cycles` | Daftar siklus (scoped) | supervisor, guru, admin_dinas | 2 |
| `GET` | `/cycles/{id}` | Detail siklus | scoped | 2 |
| `PATCH` | `/cycles/{id}/schedule` | Kunci jadwal & fokus observasi | supervisor | 2 |
| `POST` | `/cycles/{id}/reflections` | Guru submit refleksi pra-observasi | guru | 2 |
| `POST` | `/cycles/{id}/observations` | Simpan data observasi (instrumen) | supervisor | 2 |
| `PATCH` | `/observations/{id}` | Update observasi (autosave/sync, optimistic lock) | supervisor | 2 |
| `POST` | `/observations/{id}/finalize` | Final-kan observasi → transisi status 2 | supervisor | 2 |
| `POST` | `/observations/{id}/media` | Unggah media (chunked/tertunda) | supervisor | 2 |
| `GET` | `/cycles/{id}/analysis/draft` | Ambil draft analisis (Asisten AI) | supervisor | 3 `@provisional` |
| `POST` | `/cycles/{id}/analysis` | Kunci hasil analisis final (manusia) | supervisor | 3 `@provisional` |
| `POST` | `/cycles/{id}/feedback` | Rekam sesi umpan balik | supervisor | 3 `@provisional` |
| `PATCH` | `/cycles/{id}/feedback/ack` | Guru konfirmasi penerimaan umpan balik | guru | 3 `@provisional` |
| `POST` | `/cycles/{id}/follow-up` | Buat rencana tindak lanjut | supervisor | 3 `@provisional` |
| `PATCH` | `/follow-up/{id}` | Update status/checklist RTL | supervisor, guru | 3 `@provisional` |
| `PATCH` | `/follow-up/{id}/evidence` | Guru unggah bukti pelaksanaan RTL | guru | 3 `@provisional` |
| `POST` | `/cycles/{id}/cancel` | Batalkan siklus (reason) | supervisor | 2 |
| `GET` | `/reports/cycle/{id}` | Laporan per siklus | supervisor, admin_dinas | 3 `@provisional` |
| `GET` | `/reports/aggregate` | Laporan agregat lintas sekolah | admin_dinas | 3 `@provisional` |

### 2b. Endpoint sinkronisasi PWA (offline — WAJIB, Fase 2)

| Method | Endpoint | Fungsi | Scope token |
|---|---|---|---|
| `GET` | `/sync/bootstrap` | Tarik data siklus + instrumen utk kerja luring | `observation:sync` |
| `POST` | `/sync/observations` | Push batch observasi+response (idempoten, UUID klien) | `observation:sync` |
| `POST` | `/sync/observations/{id}/resolve` | Kirim resolusi konflik | `observation:sync` |
| `POST` | `/sync/follow-up-evidence` | Push antrian bukti RTL | `follow-up:evidence` |
| `GET` | `/sync/status` | Status sinkron per item | keduanya |

### 2c. AI (Fase 3 `@provisional`, semua async job)

| Method | Endpoint | Fungsi | Catatan |
|---|---|---|---|
| `POST` | `/cycles/{id}/ai/analysis-summary` | Minta draft ringkasan analisis | menghasilkan `ai_generations` status `draft` |
| `POST` | `/cycles/{id}/ai/feedback-suggestion` | Minta draft saran umpan balik | idem |
| `POST` | `/ai/generations/{id}/review` | Supervisor accept/edit/reject | wajib sebelum dipakai |

### 2d. Auth token (Fase 1)

| `POST` | `/auth/token` | Terbitkan token device (setelah login web / kredensial) |
| `DELETE` | `/auth/token/{id}` | Cabut token device |

---

## Kontrak response

```json
// sukses
{ "data": { ... }, "meta": { "version": "server-version-int" } }
// error validasi (422)
{ "errors": { "field": ["pesan"] }, "meta": {} }
// error transisi state machine (422)
{ "errors": { "transition": ["Transisi SCHEDULED→REPORTED tidak diizinkan"] } }
// konflik sync (409)
{ "errors": { "conflict": ["server_version 4 != client_base 3"] }, "data": { "server": { ... } } }
```

Setiap controller API adalah **shell tipis** yang memanggil Action/Service domain yang sama seperti komponen Livewire — logika bisnis tidak diduplikasi (ADR-008).
