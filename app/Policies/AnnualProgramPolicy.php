<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AnnualProgram;
use App\Models\User;
use App\Support\Enums\Permission;

class AnnualProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageAnnualProgram->value);
    }

    public function view(User $user, AnnualProgram $program): bool
    {
        return $program->owner_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageAnnualProgram->value);
    }

    public function update(User $user, AnnualProgram $program): bool
    {
        return $user->can(Permission::ManageAnnualProgram->value)
            && $program->owner_id === $user->getKey()
            && $program->isMutable();
    }

    public function delete(User $user, AnnualProgram $program): bool
    {
        return $this->update($user, $program)
            && $program->status === AnnualProgram::STATUS_DRAFT
            && $program->targets()->whereNotNull('cycle_id')->doesntExist();
    }
}
