<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Observation;
use App\Models\User;
use App\Support\Enums\Permission;

class ObservationPolicy
{
    public function view(User $user, Observation $observation): bool
    {
        $cycle = $observation->cycle()->sole();

        if ($user->isSupervisor() && $cycle->supervisor_id === $user->getKey()) {
            return true;
        }

        // Guru hanya melihat observasi final pada siklusnya.
        if ($user->isGuru() && $cycle->guru_id === $user->getKey()) {
            return $observation->isFinal();
        }

        return false;
    }

    public function update(User $user, Observation $observation): bool
    {
        return $user->can(Permission::ConductObservation->value)
            && $observation->observer_id === $user->getKey()
            && ! $observation->isFinal();
    }

    public function finalize(User $user, Observation $observation): bool
    {
        return $this->update($user, $observation);
    }

    public function uploadMedia(User $user, Observation $observation): bool
    {
        return $user->can(Permission::ConductObservation->value)
            && $observation->observer_id === $user->getKey();
    }
}
