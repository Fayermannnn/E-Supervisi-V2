<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BestPractice;
use App\Models\User;
use App\Support\Enums\Permission;

class BestPracticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, BestPractice $bestPractice): bool
    {
        if ($bestPractice->status === BestPractice::STATUS_TERBIT) {
            return $this->sameDinas($user, $bestPractice);
        }

        // Sebelum terbit: hanya pihak terkait + kurator dinas.
        return $user->getKey() === $bestPractice->guru_id
            || $user->getKey() === $bestPractice->nominated_by
            || $this->curates($user, $bestPractice);
    }

    public function curate(User $user, BestPractice $bestPractice): bool
    {
        return $this->curates($user, $bestPractice);
    }

    private function curates(User $user, BestPractice $bestPractice): bool
    {
        return $user->can(Permission::CurateBestPractice->value)
            && $user->adminDinasId() === $bestPractice->dinas_id;
    }

    private function sameDinas(User $user, BestPractice $bestPractice): bool
    {
        return in_array(
            $bestPractice->dinas_id,
            array_filter([$user->resolveDinasId(), $user->adminDinasId()]),
            true,
        );
    }
}
