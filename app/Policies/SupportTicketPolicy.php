<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Enums\Permission;

class SupportTicketPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageSupportTickets->value);
    }

    public function view(User $actor, SupportTicket $ticket): bool
    {
        return $actor->can(Permission::ManageSupportTickets->value)
            || $ticket->user_id === $actor->getKey();
    }

    public function create(User $actor): bool
    {
        return $actor->isActive();
    }

    public function update(User $actor, SupportTicket $ticket): bool
    {
        return $actor->can(Permission::ManageSupportTickets->value);
    }
}
