<?php

declare(strict_types=1);

namespace App\Domain\Instruments;

/**
 * CONTOH Format B (observasi pelaksanaan pembelajaran) — **belum tervalidasi**.
 * Struktur item final menunggu Artikel 2 (validasi psikometrik). Dipakai untuk
 * seed & demo agar alur end-to-end dapat diuji (ADR-013, risk R-05).
 */
final class FormatBTemplate
{
    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $likert = static fn (string $key, string $label): array => [
            'key' => $key,
            'label' => $label,
            'type' => 'likert',
            'scale' => ['min' => 1, 'max' => 4],
            'weight' => 1,
            'required' => true,
        ];

        return [
            'meta' => [
                'title' => 'Format B — Observasi Pelaksanaan Pembelajaran (CONTOH)',
                'validated' => false,
                'note' => 'Instrumen contoh untuk demo. Bukan instrumen tervalidasi.',
            ],
            'sections' => [
                [
                    'key' => 'pendahuluan',
                    'title' => 'Kegiatan Pendahuluan',
                    'items' => [
                        $likert('apersepsi', 'Melakukan apersepsi dan mengaitkan materi dengan pengalaman peserta didik'),
                        $likert('tujuan', 'Menyampaikan tujuan pembelajaran dan kegiatan yang akan dilakukan'),
                        $likert('motivasi', 'Memberikan motivasi belajar sesuai konteks'),
                    ],
                ],
                [
                    'key' => 'inti',
                    'title' => 'Kegiatan Inti',
                    'items' => [
                        $likert('penguasaan_materi', 'Menunjukkan penguasaan materi pembelajaran'),
                        $likert('strategi', 'Menerapkan strategi pembelajaran yang mengaktifkan peserta didik'),
                        $likert('media', 'Memanfaatkan media/sumber belajar secara efektif'),
                        $likert('interaksi', 'Menumbuhkan partisipasi aktif melalui interaksi guru–peserta didik'),
                        $likert('bahasa', 'Menggunakan bahasa yang benar dan tepat'),
                    ],
                ],
                [
                    'key' => 'penutup',
                    'title' => 'Kegiatan Penutup',
                    'items' => [
                        $likert('rangkuman', 'Memfasilitasi peserta didik membuat rangkuman'),
                        $likert('refleksi', 'Melakukan refleksi dan umpan balik terhadap proses pembelajaran'),
                        $likert('tindak_lanjut', 'Memberikan tindak lanjut (tugas/pengayaan/remedial)'),
                        [
                            'key' => 'catatan_umum',
                            'label' => 'Catatan kualitatif observer',
                            'type' => 'text',
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function scoringConfig(): array
    {
        return [
            'method' => 'weighted_mean_normalized', // rata-rata skor 0..1 berbobot
            'section_weights' => [
                'pendahuluan' => 1,
                'inti' => 2,
                'penutup' => 1,
            ],
            'bands' => [
                ['min' => 0.85, 'label' => 'Sangat Baik'],
                ['min' => 0.70, 'label' => 'Baik'],
                ['min' => 0.55, 'label' => 'Cukup'],
                ['min' => 0.0, 'label' => 'Perlu Pembinaan'],
            ],
        ];
    }
}
