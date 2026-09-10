<?php

declare(strict_types=1);

namespace App\Domain\Ai\Providers;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Data\AiRequest;
use App\Domain\Ai\Data\AiResult;

/**
 * Penyedia AI tiruan — deterministik, tanpa kunci API, tanpa biaya token.
 * Default sistem (ADR-009). Menyusun teks dari data nyata pada `context`
 * sehingga uji otomatis stabil dan demo end-to-end dapat berjalan.
 */
class MockAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function generate(AiRequest $request): AiResult
    {
        $ctx = $request->context;

        $output = match ($request->promptKey) {
            'analysis_summary' => $this->analysisSummary($ctx),
            'feedback_suggestion' => $this->feedbackSuggestion($ctx),
            'followup_pattern_warning' => $this->followupPattern($ctx),
            'aggregate_anomaly' => $this->aggregateAnomaly($ctx),
            default => "Draf otomatis untuk '{$request->promptKey}'.\n\n".$request->renderedPrompt,
        };

        return new AiResult(
            output: rtrim($output)."\n\n_(Draf disusun MockAiProvider — wajib ditinjau supervisor.)_",
            provider: 'mock',
            model: 'mock-deterministic-v1',
            tokenUsage: ['prompt' => str_word_count($request->renderedPrompt), 'completion' => str_word_count($output)],
        );
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function analysisSummary(array $ctx): string
    {
        $total = $ctx['score_summary']['total'] ?? null;
        $band = $ctx['score_summary']['band'] ?? '—';
        $strengths = $this->bullets($ctx['strengths'] ?? []);
        $growth = $this->bullets($ctx['growth_areas'] ?? []);

        $skorLine = $total !== null
            ? 'Skor keseluruhan '.number_format((float) $total * 100, 0).'% (kategori: '.$band.').'
            : 'Skor keseluruhan belum lengkap.';

        return <<<TXT
        RINGKASAN ANALISIS HASIL OBSERVASI

        {$skorLine}

        Kekuatan yang teramati:
        {$strengths}

        Area yang dapat dikembangkan:
        {$growth}

        Rekomendasi awal: fokuskan pembinaan pada satu-dua area di atas melalui
        pendampingan terjadwal, lalu pantau dampaknya pada siklus berikutnya.
        TXT;
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function feedbackSuggestion(array $ctx): string
    {
        $growth = $this->bullets($ctx['growth_areas'] ?? []);

        return <<<TXT
        SARAN PERCAKAPAN UMPAN BALIK (REFLEKTIF-KOLABORATIF)

        Pembuka: apresiasi capaian guru pada aspek yang sudah kuat.

        Pertanyaan reflektif yang dapat diajukan:
        - "Bagian mana dari pembelajaran tadi yang menurut Ibu/Bapak paling berhasil, dan mengapa?"
        - "Jika mengulang, apa satu hal yang ingin Ibu/Bapak ubah?"

        Area yang perlu didiskusikan bersama:
        {$growth}

        Penutup: sepakati satu langkah konkret sebagai rencana tindak lanjut.
        TXT;
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function followupPattern(array $ctx): string
    {
        $count = (int) ($ctx['overdue_count'] ?? 0);

        return "PERINGATAN POLA TINDAK LANJUT\n\n".
            "Terpantau {$count} rencana tindak lanjut melewati tenggat pada guru ini. ".
            'Pola keterlambatan berulang dapat menandakan beban kerja, target yang kurang realistis, '.
            'atau kebutuhan pendampingan lebih dekat. Keputusan eskalasi tetap pada supervisor.';
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function aggregateAnomaly(array $ctx): string
    {
        $flags = $this->bullets($ctx['flags'] ?? []);

        return "ANOMALI LAPORAN AGREGAT (UNTUK TINJAUAN ADMIN DINAS)\n\n{$flags}\n\n".
            'Tandai untuk verifikasi manual. Sistem tidak mengambil tindakan otomatis.';
    }

    /**
     * @param  list<mixed>  $items
     */
    private function bullets(array $items): string
    {
        if ($items === []) {
            return '- (tidak ada data)';
        }

        return implode("\n", array_map(static fn ($i): string => '- '.(string) $i, $items));
    }
}
