# Asisten AI — Abstraction Layer (IMPLEMENTED, Fase 3 — `@provisional`)

Sumber: Spec §4, §6.6, §10, §11; master prompt §5, §16, RULE 4.

## Implementasi

| Bagian | Berkas |
|---|---|
| Kontrak | `App\Domain\Ai\Contracts\AiProvider` — `generate(AiRequest): AiResult` |
| Penyedia | `Providers\{MockAiProvider (default), OpenAiProvider, AnthropicProvider}` |
| Pemilihan | `AiServiceProvider` — dari `config('ai.provider')`; **tanpa kunci API → paksa `mock`** |
| Prompt | `ai_prompt_templates` (berversi, `key`+`version` unik) + `PromptRenderer` (`{{ var }}` / `{{ json:var }}`) |
| Orkestrasi | `Actions\GenerateAiDraft` (otorisasi `ai.request_draft`, rate-limit/menit) → `Jobs\RunAiGeneration` (async) |
| Penyimpanan | `ai_generations` — `provider, model, prompt_key, prompt_version, source, input_context, output, status, review_status, reviewer_id, generated_at, token_usage` |
| Tinjauan | `Actions\ReviewAiGeneration` — `accept` / `edit` / `reject`; `AiGeneration::usableText()` mengembalikan `null` sampai `isHumanApproved()` |

## Gerbang human-in-the-loop yang ditegakkan (diuji)

- `AiGeneration::review_status` **tidak pernah** otomatis `accepted`/`edited` — hanya lewat `ReviewAiGeneration` oleh user dengan `ai.review_draft`.
- `FinalizeAnalysis` **menolak** ringkasan dengan `sumber = ai_draft` (AI mentah belum ditinjau).
- `PostFeedbackMessage` **menolak** pesan bersumber AI yang belum `isHumanApproved`.
- `AcknowledgeFeedback` **menolak** bila masih ada saran AI belum ditinjau di sesi.
- Tidak ada jalur kode di mana `AiProvider` mengakses DB (arch: layer `Ai` tidak meng-`use` `DB`/`Model`).
- Tidak ada transisi `CycleStateMachine` yang dapat dipicu aktor `null`/AI pada edge bergerbang-manusia.

## Prinsip

- AI = **assistive system**, bukan user, bukan pengambil keputusan.
- Setiap keluaran berstatus **`draft`** sampai ditinjau manusia.
- AI **tidak** boleh: mengambil keputusan final, mengunci analisis, mengirim umpan balik final, mengubah status siklus.
- AI **tidak** punya akses DB langsung — konteks dibangun oleh Action pemanggil (supervisor) dari data yang sudah lolos Policy.
- Aplikasi **tidak terkunci** pada satu provider.

## Kontrak

```php
namespace App\Domain\Ai\Contracts;

interface AiProvider
{
    public function generate(AiRequest $request): AiResult; // { output, tokenUsage, model }
}
```

Implementasi: `MockAiProvider` (default), `OpenAiProvider`, `AnthropicProvider`.
Dipilih via `config('ai.provider')` / `AI_PROVIDER` env. Tanpa kunci API → paksa `mock`.

## Kapabilitas (Spec §11 — roadmap)

| Tahap | Kapabilitas | Prompt key | Human-in-the-loop |
|---|---|---|---|
| 1 | Ringkasan otomatis catatan observasi | `analysis_summary` | Supervisor wajib tinjau sebelum simpan sebagai analisis final |
| 2 | Saran draf umpan balik reflektif | `feedback_suggestion` | Supervisor menyunting bahasa & substansi sebelum kirim |
| 3 | Deteksi pola RTL berulang tertunda | `followup_pattern_warning` | Sistem hanya memperingatkan; eskalasi tetap manual |
| 4 | Rekomendasi materi PKB dari riwayat siklus | `pkb_recommendation` | Opsional; guru bebas memilih alternatif |
| 5 | Deteksi anomali laporan agregat | `aggregate_anomaly` | Hanya menandai untuk tinjauan Admin Dinas |

## Penyimpanan (`ai_generations`)

`provider`, `model`, `prompt_key`, `prompt_version`, `source_type`, `source_id`, `input_context` (jsonb), `output`, `generated_at`, `reviewer_id`, `review_status` (`draft`|`accepted`|`edited`|`rejected`), `reviewed_at`, `token_usage`.

`ai_prompt_templates`: `key`, `version`, `template`, `variabel`, `aktif` — prompt berversi & dapat diaudit.

## Eksekusi

- Semua panggilan AI = **queued Job** (`GenerateAiDraft`), tidak memblok request.
- Timeout + retry terbatas; kegagalan → notifikasi "draft AI gagal, lanjutkan manual".
- Rate limit per user/siklus.

## Guard di state machine (lihat `state-machine.md`)

- `finalizeAnalysis` menolak bila `finalized_by` bukan manusia atau `sumber = ai_draft` tanpa `reviewed_by`.
- Pengiriman umpan balik final menolak konten identik dengan `ai_generations.output` yang belum `accepted`/`edited`.
- Tidak ada transisi status yang dapat dipicu agen AI.
