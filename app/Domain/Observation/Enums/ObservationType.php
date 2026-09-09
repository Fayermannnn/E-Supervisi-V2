<?php

declare(strict_types=1);

namespace App\Domain\Observation\Enums;

enum ObservationType: string
{
    case Sinkron = 'sinkron';
    case Asinkron = 'asinkron';

    public function label(): string
    {
        return match ($this) {
            self::Sinkron => 'Sinkron (tatap muka / daring langsung)',
            self::Asinkron => 'Asinkron (rekaman)',
        };
    }
}
