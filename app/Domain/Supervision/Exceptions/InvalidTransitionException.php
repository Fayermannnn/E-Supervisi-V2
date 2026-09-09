<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Exceptions;

use App\Support\Enums\CycleStatus;
use RuntimeException;

class InvalidTransitionException extends RuntimeException
{
    public static function notAllowed(CycleStatus $from, CycleStatus $to): self
    {
        return new self("Transisi {$from->name} → {$to->name} tidak diizinkan.");
    }

    public static function guardFailed(CycleStatus $from, CycleStatus $to, string $why): self
    {
        return new self("Transisi {$from->name} → {$to->name} ditolak: {$why}");
    }
}
