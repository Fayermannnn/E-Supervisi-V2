<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Administration\PolicySettings;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\Permission;

class SupervisionCyclePolicy
{
    public function __construct(private readonly PolicySettings $policies) {}

    public function viewAny(User $user): bool
    {
        return $user->isGuru() || $user->isSupervisor() || $user->isAdminDinas();
    }

    public function view(User $user, SupervisionCycle $cycle): bool
    {
        if ($user->isSupervisor() && $cycle->supervisor_id === $user->getKey()) {
            return true;
        }

        if ($user->isGuru() && $cycle->guru_id === $user->getKey()) {
            return true;
        }

        if ($user->isAdminDinas() && $cycle->dinas_id === $user->adminDinasId()) {
            return (bool) $this->policies->get('dinas.can_view_cycle_detail', $cycle->dinas_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateCycle->value);
    }

    public function update(User $user, SupervisionCycle $cycle): bool
    {
        return $user->can(Permission::ScheduleCycle->value)
            && $cycle->supervisor_id === $user->getKey()
            && $cycle->isActive();
    }

    public function schedule(User $user, SupervisionCycle $cycle): bool
    {
        return $this->update($user, $cycle);
    }

    public function cancel(User $user, SupervisionCycle $cycle): bool
    {
        return $user->can(Permission::CancelCycle->value)
            && $cycle->supervisor_id === $user->getKey()
            && $cycle->isActive();
    }

    public function observe(User $user, SupervisionCycle $cycle): bool
    {
        return $user->can(Permission::ConductObservation->value)
            && $cycle->supervisor_id === $user->getKey();
    }

    public function submitReflection(User $user, SupervisionCycle $cycle): bool
    {
        return $user->can(Permission::SubmitReflection->value)
            && $cycle->guru_id === $user->getKey();
    }

    public function agree(User $user, SupervisionCycle $cycle): bool
    {
        if (! $user->can(Permission::AgreePlanning->value)) {
            return false;
        }

        return $cycle->supervisor_id === $user->getKey() || $cycle->guru_id === $user->getKey();
    }
}
