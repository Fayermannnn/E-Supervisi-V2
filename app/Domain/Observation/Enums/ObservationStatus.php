<?php

declare(strict_types=1);

namespace App\Domain\Observation\Enums;

enum ObservationStatus: string
{
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return $this === self::Draft ? 'Draf' : 'Final';
    }
}
