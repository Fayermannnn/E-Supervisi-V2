<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AiPromptTemplate;
use Illuminate\Database\Seeder;

class AiPromptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'analysis_summary' => <<<'TXT'
            Anda membantu supervisor menyusun DRAF ringkasan analisis hasil observasi
            pembelajaran. Gunakan data berikut. Tulis ringkas, obyektif, Bahasa Indonesia baku.

            Skor per seksi: {{ json:score_summary.sections }}
            Skor total (0..1): {{ score_summary.total }} — kategori: {{ score_summary.band }}
            Kekuatan teramati: {{ strengths }}
            Area pengembangan: {{ growth_areas }}
            Catatan naratif observer: {{ catatan_skrip }}

            Susun: (1) kalimat pembuka capaian umum, (2) 2-3 kekuatan, (3) 2-3 area
            pengembangan dengan bukti, (4) rekomendasi awal. Tandai sebagai DRAF.
            TXT,

            'feedback_suggestion' => <<<'TXT'
            Susun DRAF kerangka percakapan umpan balik reflektif-kolaboratif antara
            supervisor dan guru berdasarkan area pengembangan berikut: {{ growth_areas }}.
            Sertakan 2 pertanyaan reflektif terbuka dan usulan 1 langkah tindak lanjut.
            Bahasa Indonesia, apresiatif, tidak menggurui.
            TXT,

            'followup_pattern_warning' => <<<'TXT'
            {{ overdue_count }} rencana tindak lanjut guru ini melewati tenggat.
            Jelaskan kemungkinan penyebab pola keterlambatan berulang dan sarankan
            bentuk pendampingan. Tegaskan keputusan eskalasi tetap pada supervisor.
            TXT,

            'aggregate_anomaly' => <<<'TXT'
            Tinjau indikasi anomali berikut pada laporan agregat: {{ flags }}.
            Rangkum untuk Admin Dinas sebagai bahan verifikasi manual. Jangan
            menyarankan tindakan otomatis.
            TXT,
        ];

        foreach ($templates as $key => $template) {
            AiPromptTemplate::updateOrCreate(
                ['key' => $key, 'version' => 1],
                ['template' => $template, 'is_active' => true],
            );
        }
    }
}
