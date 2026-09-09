<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Enums\Permission;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageUsers->value);
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can(Permission::ManageUsers->value) || $actor->is($user);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManageUsers->value);
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can(Permission::ManageUsers->value);
    }

    public function delete(User $actor, User $user): bool
    {
        // Admin sistem tidak boleh menghapus/menonaktifkan dirinya sendiri.
        return $actor->can(Permission::ManageUsers->value) && ! $actor->is($user);
    }

    public function resetPassword(User $actor, User $user): bool
    {
        return $actor->can(Permission::ManageUsers->value);
    }
}
