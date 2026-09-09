<?php

declare(strict_types=1);

namespace App\Domain\Observation\Exceptions;

use App\Models\Observation;
use RuntimeException;

class ObservationConflictException extends RuntimeException
{
    public function __construct(
        public readonly Observation $serverObservation,
        public readonly int $clientBaseVersion,
    ) {
        parent::__construct(
            "Konflik versi observasi: klien berbasis v{$clientBaseVersion}, server pada v{$serverObservation->version}."
        );
    }
}
