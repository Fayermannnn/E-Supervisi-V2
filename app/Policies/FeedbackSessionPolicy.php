<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FeedbackSession;
use App\Models\User;
use App\Support\Enums\Permission;

class FeedbackSessionPolicy
{
    public function view(User $user, FeedbackSession $session): bool
    {
        $cycle = $session->cycle()->sole();

        return $cycle->supervisor_id === $user->getKey() || $cycle->guru_id === $user->getKey();
    }

    public function record(User $user, FeedbackSession $session): bool
    {
        return $user->can(Permission::RecordFeedback->value)
            && $session->cycle()->sole()->supervisor_id === $user->getKey();
    }

    public function acknowledge(User $user, FeedbackSession $session): bool
    {
        return $user->can(Permission::AcknowledgeFeedback->value)
            && $session->cycle()->sole()->guru_id === $user->getKey();
    }
}
