<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Domain\Identity\RolePermissionMap;
use App\Models\Dinas;
use App\Models\RoleAssignment;
use App\Support\Enums\Permission;
use App\Support\Enums\Role;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Perilaku peran & kemampuan untuk model User. Lihat docs/rbac.md.
 */
trait HasRoles
{
    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    /**
     * Ambil penugasan peran tanpa memicu pelanggaran strict-mode lazy load.
     *
     * @return Collection<int, RoleAssignment>
     */
    protected function resolvedRoleAssignments(): Collection
    {
        return $this->relationLoaded('roleAssignments')
            ? $this->roleAssignments
            : $this->roleAssignments()->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function roles(): Collection
    {
        $roles = [];

        foreach ($this->resolvedRoleAssignments() as $assignment) {
            $role = Role::tryFrom($assignment->role);

            if ($role !== null) {
                $roles[] = $role;
            }
        }

        return collect($roles);
    }

    public function hasRole(Role $role): bool
    {
        return $this->roles()->contains($role);
    }

    public function hasAnyRole(Role ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(Permission $permission): bool
    {
        foreach ($this->roles() as $role) {
            if (RolePermissionMap::grants($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(Role $role, ?Dinas $dinas = null, ?self $assignedBy = null): RoleAssignment
    {
        $assignment = $this->roleAssignments()->updateOrCreate(
            ['role' => $role->value],
            [
                'dinas_id' => $dinas?->getKey(),
                'assigned_by' => $assignedBy?->getKey(),
            ],
        );

        $this->unsetRelation('roleAssignments');

        return $assignment;
    }

    public function revokeRole(Role $role): void
    {
        $this->roleAssignments()->where('role', $role->value)->delete();
        $this->unsetRelation('roleAssignments');
    }

    public function isGuru(): bool
    {
        return $this->hasRole(Role::Guru);
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole(Role::Supervisor);
    }

    public function isAdminDinas(): bool
    {
        return $this->hasRole(Role::AdminDinas);
    }

    public function isAdminSistem(): bool
    {
        return $this->hasRole(Role::AdminSistem);
    }

    /**
     * dinas_id yang menjadi lingkup Admin Dinas (dari role_assignments).
     */
    public function adminDinasId(): ?string
    {
        foreach ($this->resolvedRoleAssignments() as $assignment) {
            if ($assignment->role === Role::AdminDinas->value) {
                return $assignment->dinas_id;
            }
        }

        return null;
    }
}
