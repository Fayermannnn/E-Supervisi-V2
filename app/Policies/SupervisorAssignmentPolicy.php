<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\Permission;

class SupervisorAssignmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageAssignments->value);
    }

    public function view(User $actor, SupervisorAssignment $assignment): bool
    {
        return $this->withinScope($actor, $assignment);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManageAssignments->value);
    }

    public function update(User $actor, SupervisorAssignment $assignment): bool
    {
        return $this->withinScope($actor, $assignment);
    }

    public function delete(User $actor, SupervisorAssignment $assignment): bool
    {
        return $this->withinScope($actor, $assignment);
    }

    private function withinScope(User $actor, SupervisorAssignment $assignment): bool
    {
        if (! $actor->can(Permission::ManageAssignments->value)) {
            return false;
        }

        if ($actor->isAdminSistem()) {
            return true;
        }

        $dinasId = $actor->adminDinasId();

        if ($dinasId === null) {
            return false;
        }

        // Kedua pihak penugasan harus berada di dinas admin tersebut.
        return $assignment->supervisor?->resolveDinasId() === $dinasId
            && $assignment->guru?->resolveDinasId() === $dinasId;
    }
}
