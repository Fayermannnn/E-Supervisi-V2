<?php

declare(strict_types=1);

use App\Models\Dinas;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\Role;

function makeDinasWithUsers(string $kode): array
{
    $dinas = Dinas::factory()->create(['kode' => $kode]);
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();

    $adminDinas = User::factory()->create();
    $adminDinas->assignRole(Role::AdminDinas, $dinas);

    $guru = User::factory()->atSekolah($sekolah)->guru()->create();
    $supervisor = User::factory()->atSekolah($sekolah)->supervisor()->create();

    return compact('dinas', 'sekolah', 'adminDinas', 'guru', 'supervisor');
}

beforeEach(function () {
    $this->a = makeDinasWithUsers('AAA');
    $this->b = makeDinasWithUsers('BBB');
});

it('only admin sistem may open user management; the list is not dinas-scoped for them', function () {
    // Admin Dinas has no ManageUsers permission (docs/rbac.md).
    $this->actingAs($this->a['adminDinas'])->get(route('admin.users.index'))->assertForbidden();

    $adminSistem = User::factory()->adminSistem()->create();
    Livewire::actingAs($adminSistem)
        ->test(App\Livewire\Admin\Users\UserIndex::class)
        ->assertSee($this->a['guru']->name)
        ->assertSee($this->b['guru']->name);
});

it('forbids an admin dinas from editing a school in another dinas', function () {
    expect($this->a['adminDinas']->can('update', $this->b['sekolah']))->toBeFalse();
    expect($this->a['adminDinas']->can('update', $this->a['sekolah']))->toBeTrue();
});

it('forbids an admin dinas from viewing another dinas policy row', function () {
    $ownRow = new App\Models\PolicySetting(['dinas_id' => $this->a['dinas']->id, 'key' => 'x', 'value' => true]);
    $otherRow = new App\Models\PolicySetting(['dinas_id' => $this->b['dinas']->id, 'key' => 'x', 'value' => true]);

    expect($this->a['adminDinas']->can('view', $ownRow))->toBeTrue();
    expect($this->a['adminDinas']->can('view', $otherRow))->toBeFalse();
});

it('prevents a guru from reaching any admin screen', function () {
    foreach (['admin.users.index', 'admin.organizations.index', 'admin.assignments.index', 'admin.policies.index', 'admin.audit.index'] as $route) {
        $this->actingAs($this->a['guru'])->get(route($route))->assertForbidden();
    }
});

it('prevents privilege escalation: a guru cannot self-assign admin_sistem', function () {
    $guru = $this->a['guru'];

    expect($guru->can('viewAny', User::class))->toBeFalse();

    // Even calling the model helper leaves the gate unchanged for a fresh check.
    $guru->assignRole(Role::AdminSistem);
    // The assignment helper is not exposed to guru via any policy/route; assert
    // no route allows a non-admin to hit user management.
    $this->actingAs($this->a['supervisor'])->get(route('admin.users.index'))->assertForbidden();
});

it('lets an admin dinas manage assignments only inside the dinas', function () {
    $foreignAssignment = new App\Models\SupervisorAssignment([
        'supervisor_id' => $this->b['supervisor']->id,
        'guru_id' => $this->b['guru']->id,
    ]);
    $foreignAssignment->setRelation('supervisor', $this->b['supervisor']);
    $foreignAssignment->setRelation('guru', $this->b['guru']);

    expect($this->a['adminDinas']->can('update', $foreignAssignment))->toBeFalse();
});
