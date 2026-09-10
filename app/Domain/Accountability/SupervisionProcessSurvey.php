<?php

declare(strict_types=1);

namespace App\Domain\Accountability;

/**
 * Instrumen penilaian 360° — guru menilai PROSES supervisi (M11, @provisional).
 * Lima dimensi Likert 1–4. Bukan penilaian performa mengajar guru.
 */
final class SupervisionProcessSurvey
{
    public const MIN = 1;

    public const MAX = 4;

    /**
     * @return array<string, string>
     */
    public static function dimensions(): array
    {
        return [
            'kejelasan' => 'Kejelasan tujuan, fokus, dan tahapan supervisi',
            'keadilan' => 'Objektivitas dan keadilan dalam menilai',
            'dukungan' => 'Dukungan dan bimbingan yang diberikan',
            'umpan_balik' => 'Umpan balik yang spesifik, membangun, dan dapat ditindaklanjuti',
            'rasa_hormat' => 'Sikap menghargai dan komunikasi yang setara',
        ];
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, int>
     */
    public static function normalize(array $answers): array
    {
        $clean = [];
        foreach (self::dimensions() as $key => $_) {
            $value = (int) ($answers[$key] ?? 0);
            $clean[$key] = max(self::MIN, min(self::MAX, $value));
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public static function isComplete(array $answers): bool
    {
        foreach (array_keys(self::dimensions()) as $key) {
            $value = (int) ($answers[$key] ?? 0);
            if ($value < self::MIN || $value > self::MAX) {
                return false;
            }
        }

        return true;
    }
}
