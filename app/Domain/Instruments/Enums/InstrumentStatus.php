<?php

declare(strict_types=1);

namespace App\Domain\Instruments\Enums;

enum InstrumentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
            self::Archived => 'Arsip',
        };
    }
}
