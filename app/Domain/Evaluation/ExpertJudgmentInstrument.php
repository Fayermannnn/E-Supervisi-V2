<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

/**
 * Instrumen expert judgment atas artefak Sistem E-Supervisi (DSR Artikel 3,
 * Fase 5). Setiap aspek dinilai dua cara oleh tiap ahli:
 *   - relevansi (CVR / Lawshe): esensial | berguna | tidak_perlu
 *   - kualitas (Aiken's V): skala 1–5
 *
 * Aspek diturunkan dari prinsip non-negosiasi & tujuan sistem (Spec §2–3, §6).
 */
final class ExpertJudgmentInstrument
{
    public const RELEVANSI_ESENSIAL = 'esensial';

    public const RELEVANSI_BERGUNA = 'berguna';

    public const RELEVANSI_TIDAK_PERLU = 'tidak_perlu';

    public const KUALITAS_MIN = 1;

    public const KUALITAS_MAX = 5;

    /**
     * @return list<string>
     */
    public static function relevansiOptions(): array
    {
        return [self::RELEVANSI_ESENSIAL, self::RELEVANSI_BERGUNA, self::RELEVANSI_TIDAK_PERLU];
    }

    /**
     * @return array<string, string> key => pernyataan aspek
     */
    public static function aspects(): array
    {
        return [
            'kelengkapan_tahap' => 'Sistem mendukung keenam tahap siklus supervisi klinis secara utuh (perencanaan → pelaporan).',
            'kesesuaian_teori' => 'Alur dan status sistem konsisten dengan kerangka Acheson & Gall (1997) dan kode Artikel 1.',
            'human_in_the_loop' => 'Mekanisme human-in-the-loop pada fitur AI memadai — keluaran AI tidak pernah menjadi keputusan final tanpa tinjauan manusia.',
            'keamanan_privasi' => 'Kontrol akses berbasis peran dan pelindungan data pribadi (UU 27/2022) memadai untuk data guru.',
            'dukungan_luring' => 'Dukungan mode luring pada observasi dan bukti tindak lanjut sesuai untuk konteks infrastruktur terbatas (3T).',
            'tindak_lanjut' => 'Fitur tindak lanjut (RTL, pengingat, eskalasi) memperkuat mata rantai yang lemah pada supervisi konvensional.',
            'pelaporan_data' => 'Pelaporan berbasis data (per siklus & agregat) berguna untuk pengambilan keputusan dinas.',
            'pengembangan_profesional' => 'Katalog PKB, perpustakaan praktik baik, akuntabilitas 360°, dan kalibrasi antar-penilai relevan untuk mutu supervisi berkelanjutan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $jawaban
     */
    public static function isComplete(array $jawaban): bool
    {
        $relevansi = is_array($jawaban['relevansi'] ?? null) ? $jawaban['relevansi'] : [];
        $kualitas = is_array($jawaban['kualitas'] ?? null) ? $jawaban['kualitas'] : [];

        foreach (array_keys(self::aspects()) as $aspek) {
            if (! in_array($relevansi[$aspek] ?? null, self::relevansiOptions(), true)) {
                return false;
            }
            $q = (int) ($kualitas[$aspek] ?? 0);
            if ($q < self::KUALITAS_MIN || $q > self::KUALITAS_MAX) {
                return false;
            }
        }

        return UsabilityQuestionnaire::isComplete(is_array($jawaban['sus'] ?? null) ? $jawaban['sus'] : []);
    }

    /**
     * @param  array<string, mixed>  $jawaban
     * @return array<string, mixed>
     */
    public static function normalize(array $jawaban): array
    {
        $relevansi = [];
        $kualitas = [];
        $inRelevansi = is_array($jawaban['relevansi'] ?? null) ? $jawaban['relevansi'] : [];
        $inKualitas = is_array($jawaban['kualitas'] ?? null) ? $jawaban['kualitas'] : [];

        foreach (array_keys(self::aspects()) as $aspek) {
            $r = $inRelevansi[$aspek] ?? null;
            $relevansi[$aspek] = in_array($r, self::relevansiOptions(), true) ? $r : self::RELEVANSI_BERGUNA;
            $kualitas[$aspek] = max(self::KUALITAS_MIN, min(self::KUALITAS_MAX, (int) ($inKualitas[$aspek] ?? self::KUALITAS_MIN)));
        }

        return [
            'relevansi' => $relevansi,
            'kualitas' => $kualitas,
            'sus' => UsabilityQuestionnaire::normalize(is_array($jawaban['sus'] ?? null) ? $jawaban['sus'] : []),
        ];
    }
}
