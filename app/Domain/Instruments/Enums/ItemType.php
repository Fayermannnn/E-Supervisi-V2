<?php

declare(strict_types=1);

namespace App\Domain\Instruments\Enums;

/**
 * Tipe jawaban item instrumen (Format A–E — schema-driven, ADR-013).
 * Struktur item final menunggu validasi Artikel 2; tipe di sini generik.
 */
enum ItemType: string
{
    case Likert = 'likert';       // skala terurut, mis. 1..4
    case Numeric = 'numeric';     // skor angka bebas dalam rentang
    case Boolean = 'boolean';     // ya / tidak
    case Text = 'text';           // catatan naratif
    case Checklist = 'checklist'; // pilih beberapa dari daftar

    public function label(): string
    {
        return match ($this) {
            self::Likert => 'Skala Likert',
            self::Numeric => 'Skor Angka',
            self::Boolean => 'Ya / Tidak',
            self::Text => 'Teks',
            self::Checklist => 'Checklist',
        };
    }

    /**
     * Apakah tipe ini menyumbang skor kuantitatif?
     */
    public function isScorable(): bool
    {
        return $this === self::Likert || $this === self::Numeric || $this === self::Boolean;
    }
}
