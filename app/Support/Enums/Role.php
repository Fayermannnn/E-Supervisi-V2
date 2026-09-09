<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Peran sistem (Spec §4). Empat peran tetap. Lihat docs/rbac.md.
 *
 * Peran disimpan sebagai baris di `role_assignments` (bukan kolom pada users)
 * agar dapat diberi lingkup dinas dan diaudit.
 */
enum Role: string
{
    case Guru = 'guru';
    case Supervisor = 'supervisor';
    case AdminDinas = 'admin_dinas';
    case AdminSistem = 'admin_sistem';

    public function label(): string
    {
        return match ($this) {
            self::Guru => 'Guru',
            self::Supervisor => 'Supervisor',
            self::AdminDinas => 'Admin Dinas',
            self::AdminSistem => 'Admin Sistem',
        };
    }

    /**
     * Peran ini terikat pada satu dinas tertentu?
     */
    public function isDinasScoped(): bool
    {
        return $this === self::AdminDinas;
    }

    /**
     * Peran ini terikat pada satu sekolah tertentu?
     */
    public function isSekolahScoped(): bool
    {
        return $this === self::Guru || $this === self::Supervisor;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $r): string => $r->value, self::cases());
    }
}
