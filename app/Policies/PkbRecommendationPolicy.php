<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PkbRecommendation;
use App\Models\User;

class PkbRecommendationPolicy
{
    public function view(User $user, PkbRecommendation $recommendation): bool
    {
        $cycle = $recommendation->cycle()->sole();

        return $user->getKey() === $cycle->guru_id || $user->getKey() === $cycle->supervisor_id;
    }

    public function respond(User $user, PkbRecommendation $recommendation): bool
    {
        return $this->view($user, $recommendation);
    }
}
