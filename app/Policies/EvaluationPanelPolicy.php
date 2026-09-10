<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EvaluationPanel;
use App\Models\User;
use App\Support\Enums\Permission;

class EvaluationPanelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageEvaluationPanel->value)
            || $user->can(Permission::SubmitExpertReview->value);
    }

    public function view(User $user, EvaluationPanel $panel): bool
    {
        if ($user->can(Permission::ManageEvaluationPanel->value)) {
            return true;
        }

        return $panel->experts()->where('user_id', $user->getKey())->exists();
    }

    public function manage(User $user, EvaluationPanel $panel): bool
    {
        return $user->can(Permission::ManageEvaluationPanel->value);
    }

    public function review(User $user, EvaluationPanel $panel): bool
    {
        return $user->can(Permission::SubmitExpertReview->value)
            && $panel->experts()->where('user_id', $user->getKey())->exists();
    }
}
