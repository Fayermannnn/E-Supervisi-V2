<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Events;

use App\Models\CycleStatusTransition;
use App\Models\SupervisionCycle;
use App\Support\Enums\CycleStatus;
use Illuminate\Foundation\Events\Dispatchable;

class CycleTransitioned
{
    use Dispatchable;

    public function __construct(
        public readonly SupervisionCycle $cycle,
        public readonly CycleStatus $from,
        public readonly CycleStatus $to,
        public readonly CycleStatusTransition $transition,
    ) {}
}
