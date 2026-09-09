<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Instrument;
use App\Models\User;
use App\Support\Enums\Permission;

class InstrumentPolicy
{
    public function viewAny(User $user): bool
    {
        // Supervisor perlu melihat bank instrumen untuk memilih saat perencanaan.
        return $user->can(Permission::ManageInstruments->value) || $user->isSupervisor();
    }

    public function view(User $user, Instrument $instrument): bool
    {
        if ($instrument->isGlobal()) {
            return $this->viewAny($user);
        }

        if ($user->isAdminSistem()) {
            return true;
        }

        return $instrument->pemilik_dinas_id === $user->resolveDinasId();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageInstruments->value);
    }

    public function update(User $user, Instrument $instrument): bool
    {
        if (! $user->can(Permission::ManageInstruments->value)) {
            return false;
        }

        if ($user->isAdminSistem()) {
            return true;
        }

        // Admin Dinas tidak boleh menyunting instrumen global.
        return ! $instrument->isGlobal() && $instrument->pemilik_dinas_id === $user->adminDinasId();
    }

    public function delete(User $user, Instrument $instrument): bool
    {
        return $this->update($user, $instrument);
    }
}
