<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AnalysisResult;
use App\Models\User;
use App\Support\Enums\Permission;

class AnalysisResultPolicy
{
    public function view(User $user, AnalysisResult $result): bool
    {
        $cycle = $result->cycle()->sole();

        if ($user->isSupervisor() && $cycle->supervisor_id === $user->getKey()) {
            return true;
        }

        // Guru melihat analisis final pada siklusnya.
        return $user->isGuru() && $cycle->guru_id === $user->getKey() && $result->isFinal();
    }

    public function perform(User $user, AnalysisResult $result): bool
    {
        return $user->can(Permission::PerformAnalysis->value)
            && $result->cycle()->sole()->supervisor_id === $user->getKey()
            && ! $result->isFinal();
    }

    public function update(User $user, AnalysisResult $result): bool
    {
        return $this->perform($user, $result);
    }

    public function finalize(User $user, AnalysisResult $result): bool
    {
        return $user->can(Permission::FinalizeAnalysis->value)
            && $result->cycle()->sole()->supervisor_id === $user->getKey();
    }

    public function requestAiDraft(User $user, AnalysisResult $result): bool
    {
        return $user->can(Permission::RequestAiDraft->value)
            && $result->cycle()->sole()->supervisor_id === $user->getKey();
    }
}
