<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\Permission;

class SekolahPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageOrganization->value);
    }

    public function view(User $actor, Sekolah $sekolah): bool
    {
        return $this->withinScope($actor, $sekolah->dinas_id);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManageOrganization->value);
    }

    public function update(User $actor, Sekolah $sekolah): bool
    {
        return $this->withinScope($actor, $sekolah->dinas_id);
    }

    public function delete(User $actor, Sekolah $sekolah): bool
    {
        return $actor->isAdminSistem();
    }

    private function withinScope(User $actor, ?string $dinasId): bool
    {
        if ($actor->isAdminSistem()) {
            return true;
        }

        return $actor->can(Permission::ManageOrganization->value)
            && $dinasId !== null
            && $actor->adminDinasId() === $dinasId;
    }
}
