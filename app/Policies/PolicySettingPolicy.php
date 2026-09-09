<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PolicySetting;
use App\Models\User;
use App\Support\Enums\Permission;

class PolicySettingPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManagePolicySettings->value);
    }

    public function view(User $actor, PolicySetting $setting): bool
    {
        return $this->withinScope($actor, $setting->dinas_id);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManagePolicySettings->value);
    }

    public function update(User $actor, PolicySetting $setting): bool
    {
        return $this->withinScope($actor, $setting->dinas_id);
    }

    public function delete(User $actor, PolicySetting $setting): bool
    {
        // Baris global hanya boleh disentuh admin sistem.
        return $this->withinScope($actor, $setting->dinas_id) && $setting->dinas_id !== null;
    }

    private function withinScope(User $actor, ?string $dinasId): bool
    {
        if (! $actor->can(Permission::ManagePolicySettings->value)) {
            return false;
        }

        if ($actor->isAdminSistem()) {
            return true;
        }

        // Admin Dinas: hanya baris milik dinasnya (bukan baris global).
        return $dinasId !== null && $actor->adminDinasId() === $dinasId;
    }
}
