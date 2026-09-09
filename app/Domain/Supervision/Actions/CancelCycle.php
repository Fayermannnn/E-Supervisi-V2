<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Actions;

use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;

class CancelCycle
{
    public function __construct(private readonly CycleStateMachine $stateMachine) {}

    public function handle(User $actor, SupervisionCycle $cycle, string $reason): SupervisionCycle
    {
        return $this->stateMachine->transition($cycle, CycleStatus::Canceled, $actor, $reason);
    }
}
