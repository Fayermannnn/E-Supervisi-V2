<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HelpArticle;
use App\Models\User;

class HelpArticlePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActive();
    }

    public function view(User $actor, HelpArticle $article): bool
    {
        return $article->is_published || $actor->isAdminSistem();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdminSistem();
    }

    public function update(User $actor, HelpArticle $article): bool
    {
        return $actor->isAdminSistem();
    }

    public function delete(User $actor, HelpArticle $article): bool
    {
        return $actor->isAdminSistem();
    }
}
