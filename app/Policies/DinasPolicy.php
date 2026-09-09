<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dinas;
use App\Models\User;
use App\Support\Enums\Permission;

class DinasPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageOrganization->value);
    }

    public function view(User $actor, Dinas $dinas): bool
    {
        if ($actor->isAdminSistem()) {
            return true;
        }

        return $actor->can(Permission::ManageOrganization->value)
            && $actor->adminDinasId() === $dinas->getKey();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdminSistem();
    }

    public function update(User $actor, Dinas $dinas): bool
    {
        return $this->view($actor, $dinas);
    }

    public function delete(User $actor, Dinas $dinas): bool
    {
        return $actor->isAdminSistem();
    }
}
