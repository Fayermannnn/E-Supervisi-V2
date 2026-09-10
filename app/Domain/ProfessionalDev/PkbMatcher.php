<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev;

/**
 * Pencocokan deterministik antara area pengembangan (teks temuan analisis)
 * dan tag item katalog PKB (M9). Tidak memakai AI — murni irisan kata kunci.
 */
final class PkbMatcher
{
    /**
     * @var list<string>
     */
    private const STOPWORDS = [
        'yang', 'untuk', 'pada', 'dari', 'dengan', 'dan', 'atau', 'perlu', 'lebih',
        'agar', 'dalam', 'oleh', 'akan', 'telah', 'masih', 'relatif', 'skor',
        'area', 'aspek', 'bagian', 'kegiatan', 'guru', 'siswa', 'peserta', 'didik',
        'penguatan', 'pengembangan', 'kelas', 'pembelajaran',
    ];

    /**
     * @return list<string>
     */
    public static function keywords(string $text): array
    {
        $normalized = mb_strtolower(strip_tags($text));
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $keywords = [];
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 4 || in_array($token, self::STOPWORDS, true)) {
                continue;
            }
            $keywords[$token] = true;
        }

        return array_keys($keywords);
    }

    /**
     * @param  list<string>  $keywords
     * @param  iterable<array{id: string, tags: list<string>}>  $items
     * @return array<string, list<string>> itemId => tag yang cocok (urut)
     */
    public static function match(array $keywords, iterable $items): array
    {
        $matches = [];
        foreach ($items as $item) {
            $hit = [];
            foreach ($item['tags'] as $tag) {
                foreach (self::keywords($tag) as $tagWord) {
                    if (in_array($tagWord, $keywords, true)) {
                        $hit[$tag] = true;
                        break;
                    }
                }
            }
            if ($hit !== []) {
                $matches[$item['id']] = array_keys($hit);
            }
        }

        return $matches;
    }
}
