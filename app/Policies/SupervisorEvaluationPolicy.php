<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupervisionCycle;
use App\Models\SupervisorEvaluation;
use App\Models\User;
use App\Support\Enums\Permission;

class SupervisorEvaluationPolicy
{
    /**
     * Guru boleh membuka formulir 360° untuk siklusnya sendiri.
     */
    public function submitForCycle(User $user, SupervisionCycle $cycle): bool
    {
        return $user->can(Permission::SubmitSupervisorEvaluation->value)
            && $cycle->guru_id === $user->getKey();
    }

    public function view(User $user, SupervisorEvaluation $evaluation): bool
    {
        // Hanya guru pemilik. Agregat (bukan baris ini) untuk supervisor/admin dinas.
        return $evaluation->guru_id === $user->getKey();
    }
}
