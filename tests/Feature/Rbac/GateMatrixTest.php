<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Enums\Permission;
use App\Support\Enums\Role;
use Illuminate\Support\Facades\Gate;

/**
 * Memastikan setiap Permission terdaftar sebagai Gate dan menghormati
 * RolePermissionMap serta status aktif pengguna.
 */
it('registers a gate for every permission', function () {
    foreach (Permission::cases() as $permission) {
        expect(Gate::has($permission->value))->toBeTrue("Gate hilang: {$permission->value}");
    }
});

it('grants supervisor the create-cycle gate and denies it to guru', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole(Role::Supervisor);
    $guru = User::factory()->create();
    $guru->assignRole(Role::Guru);

    expect(Gate::forUser($supervisor)->allows(Permission::CreateCycle->value))->toBeTrue();
    expect(Gate::forUser($guru)->allows(Permission::CreateCycle->value))->toBeFalse();
});

it('denies every permission to an inactive user', function () {
    $user = User::factory()->inactive()->create();
    $user->assignRole(Role::AdminSistem);

    foreach (Permission::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission->value))->toBeFalse();
    }
});
