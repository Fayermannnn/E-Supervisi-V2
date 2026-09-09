<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\Dinas;
use App\Models\User;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(
        User $actor,
        string $name,
        string $email,
        Role $role,
        ?string $nip = null,
        ?string $jabatan = null,
        ?string $sekolahId = null,
        ?SupervisorType $supervisorType = null,
        ?Dinas $dinas = null,
    ): User {
        return DB::transaction(function () use ($actor, $name, $email, $role, $nip, $jabatan, $sekolahId, $supervisorType, $dinas): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'nip' => $nip,
                'jabatan' => $jabatan,
                'sekolah_id' => $sekolahId,
                'supervisor_type' => $supervisorType?->value,
                'is_active' => true,
                'password' => Str::password(24),
            ]);

            $user->assignRole($role, $dinas, $actor);

            $this->audit->log('user.created', $user, new: [
                'name' => $name,
                'email' => $email,
                'role' => $role->value,
            ], actor: $actor);

            // Kirim tautan agar pengguna menetapkan kata sandinya sendiri.
            Password::sendResetLink(['email' => $user->email]);

            return $user->refresh();
        });
    }
}
