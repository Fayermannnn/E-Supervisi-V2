<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\User;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(
        User $actor,
        User $user,
        string $name,
        string $email,
        Role $role,
        ?string $nip = null,
        ?string $jabatan = null,
        ?string $sekolahId = null,
        ?SupervisorType $supervisorType = null,
    ): User {
        return DB::transaction(function () use ($actor, $user, $name, $email, $role, $nip, $jabatan, $sekolahId, $supervisorType): User {
            $original = $user->only(['name', 'email', 'nip', 'jabatan', 'sekolah_id', 'supervisor_type']);

            $user->fill([
                'name' => $name,
                'email' => $email,
                'nip' => $nip,
                'jabatan' => $jabatan,
                'sekolah_id' => $sekolahId,
                'supervisor_type' => $supervisorType?->value,
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $currentRole = $user->roles()->first();
            if ($currentRole !== $role) {
                if ($currentRole !== null) {
                    $user->revokeRole($currentRole);
                }
                $user->assignRole($role, assignedBy: $actor);
                $this->audit->log('role.changed', $user, old: ['role' => $currentRole?->value], new: ['role' => $role->value], actor: $actor);
            }

            if ($user->wasChanged()) {
                $this->audit->log('user.updated', $user, old: $original, new: $user->getChanges(), actor: $actor);
            }

            return $user->refresh();
        });
    }
}
