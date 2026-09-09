<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Jenis supervisor (ADR-012). Peran RBAC sama (`supervisor`); yang berbeda
 * hanya lingkup binaan: kepala sekolah dibatasi sekolahnya, pengawas boleh
 * lintas sekolah dalam satu dinas.
 */
enum SupervisorType: string
{
    case KepalaSekolah = 'kepala_sekolah';
    case Pengawas = 'pengawas';

    public function label(): string
    {
        return match ($this) {
            self::KepalaSekolah => 'Kepala Sekolah',
            self::Pengawas => 'Pengawas',
        };
    }

    public function crossesSchools(): bool
    {
        return $this === self::Pengawas;
    }
}
