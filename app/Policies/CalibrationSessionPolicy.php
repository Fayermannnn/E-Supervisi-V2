<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalibrationSession;
use App\Models\User;
use App\Support\Enums\Permission;

class CalibrationSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageCalibration->value)
            || $user->can(Permission::ParticipateCalibration->value);
    }

    public function view(User $user, CalibrationSession $session): bool
    {
        if ($this->manage($user, $session)) {
            return true;
        }

        return $session->participants()->where('supervisor_id', $user->getKey())->exists();
    }

    public function manage(User $user, CalibrationSession $session): bool
    {
        if (! $user->can(Permission::ManageCalibration->value)) {
            return false;
        }

        return $user->isAdminSistem() || $user->adminDinasId() === $session->dinas_id;
    }

    public function score(User $user, CalibrationSession $session): bool
    {
        return $user->can(Permission::ParticipateCalibration->value)
            && $session->participants()->where('supervisor_id', $user->getKey())->exists();
    }
}
