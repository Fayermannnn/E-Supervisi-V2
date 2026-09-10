<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FollowUpPlan;
use App\Models\User;
use App\Support\Enums\Permission;

class FollowUpPlanPolicy
{
    public function view(User $user, FollowUpPlan $plan): bool
    {
        $cycle = $plan->cycle()->sole();

        return $cycle->supervisor_id === $user->getKey() || $cycle->guru_id === $user->getKey();
    }

    public function create(User $user, FollowUpPlan $plan): bool
    {
        return $user->can(Permission::CreateFollowUp->value)
            && $plan->cycle()->sole()->supervisor_id === $user->getKey();
    }

    public function update(User $user, FollowUpPlan $plan): bool
    {
        $cycle = $plan->cycle()->sole();

        return $user->can(Permission::UpdateFollowUp->value)
            && ($cycle->supervisor_id === $user->getKey() || $cycle->guru_id === $user->getKey());
    }
}
