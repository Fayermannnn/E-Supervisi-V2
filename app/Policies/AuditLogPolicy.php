<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Enums\Permission;

/**
 * Audit log hanya-baca (ADR-010). Tidak ada create/update/delete — metode
 * tersebut sengaja tidak didefinisikan sehingga Gate menolaknya secara default.
 */
class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ViewAuditLog->value);
    }

    public function view(User $actor, AuditLog $log): bool
    {
        if (! $actor->can(Permission::ViewAuditLog->value)) {
            return false;
        }

        if ($actor->isAdminSistem()) {
            return true;
        }

        // Admin Dinas & Supervisor: hanya baris terkait aktor di dinasnya.
        $scopeDinas = $actor->resolveDinasId();

        if ($scopeDinas === null) {
            return false;
        }

        return $log->actor?->resolveDinasId() === $scopeDinas;
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $actor, AuditLog $log): bool
    {
        return false;
    }
}
