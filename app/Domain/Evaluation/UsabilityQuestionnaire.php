<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

/**
 * System Usability Scale (Brooke, 1996) — adaptasi bahasa Indonesia. Sepuluh
 * pernyataan, skala 1 (sangat tidak setuju) – 5 (sangat setuju). Butir ganjil
 * positif, butir genap negatif. Skor akhir 0–100 (Fase 5, kuesioner usability).
 */
final class UsabilityQuestionnaire
{
    public const MIN = 1;

    public const MAX = 5;

    /**
     * @return array<string, array{teks: string, positif: bool}>
     */
    public static function items(): array
    {
        return [
            's1' => ['teks' => 'Saya rasa saya ingin sering menggunakan sistem ini.', 'positif' => true],
            's2' => ['teks' => 'Saya merasa sistem ini terlalu rumit.', 'positif' => false],
            's3' => ['teks' => 'Saya rasa sistem ini mudah digunakan.', 'positif' => true],
            's4' => ['teks' => 'Saya rasa saya butuh bantuan teknis untuk dapat menggunakan sistem ini.', 'positif' => false],
            's5' => ['teks' => 'Saya rasa fungsi-fungsi dalam sistem ini terpadu dengan baik.', 'positif' => true],
            's6' => ['teks' => 'Saya rasa terlalu banyak ketidakkonsistenan dalam sistem ini.', 'positif' => false],
            's7' => ['teks' => 'Saya rasa kebanyakan orang akan cepat memahami cara memakai sistem ini.', 'positif' => true],
            's8' => ['teks' => 'Saya rasa sistem ini sangat merepotkan untuk digunakan.', 'positif' => false],
            's9' => ['teks' => 'Saya merasa sangat percaya diri saat menggunakan sistem ini.', 'positif' => true],
            's10' => ['teks' => 'Saya perlu belajar banyak hal dulu sebelum bisa menggunakan sistem ini.', 'positif' => false],
        ];
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public static function isComplete(array $answers): bool
    {
        foreach (array_keys(self::items()) as $key) {
            $v = (int) ($answers[$key] ?? 0);
            if ($v < self::MIN || $v > self::MAX) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, int>
     */
    public static function normalize(array $answers): array
    {
        $clean = [];
        foreach (array_keys(self::items()) as $key) {
            $clean[$key] = max(self::MIN, min(self::MAX, (int) ($answers[$key] ?? self::MIN)));
        }

        return $clean;
    }

    /**
     * Skor SUS 0–100 untuk satu responden.
     *
     * @param  array<string, int>  $answers
     */
    public static function score(array $answers): float
    {
        $sum = 0;
        foreach (self::items() as $key => $meta) {
            $v = (int) ($answers[$key] ?? self::MIN);
            $sum += $meta['positif'] ? ($v - 1) : (self::MAX - $v);
        }

        return round($sum * 2.5, 1);
    }

    public static function interpret(float $score): string
    {
        return match (true) {
            $score >= 85.5 => 'Sangat baik (A)',
            $score >= 72.6 => 'Baik (B)',
            $score >= 62.7 => 'Cukup (C, di atas rata-rata)',
            $score >= 51.7 => 'Marginal (D, di bawah rata-rata)',
            default => 'Buruk (F)',
        };
    }
}
