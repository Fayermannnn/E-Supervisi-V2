<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PkbCatalogItem;
use App\Models\User;
use App\Support\Enums\Permission;

class PkbCatalogItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, PkbCatalogItem $item): bool
    {
        if ($item->status === PkbCatalogItem::STATUS_TERBIT) {
            return $item->pemilik_dinas_id === null || $item->pemilik_dinas_id === $user->resolveDinasId() || $item->pemilik_dinas_id === $user->adminDinasId();
        }

        return $this->manage($user, $item);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManagePkbCatalog->value);
    }

    public function update(User $user, PkbCatalogItem $item): bool
    {
        return $this->manage($user, $item);
    }

    public function delete(User $user, PkbCatalogItem $item): bool
    {
        return $this->manage($user, $item) && $item->status === PkbCatalogItem::STATUS_DRAFT;
    }

    private function manage(User $user, PkbCatalogItem $item): bool
    {
        if (! $user->can(Permission::ManagePkbCatalog->value)) {
            return false;
        }

        return $user->isAdminSistem() || $item->pemilik_dinas_id === $user->adminDinasId();
    }
}
