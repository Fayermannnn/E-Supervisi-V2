<?php

declare(strict_types=1);

use App\Models\Dinas;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->adminSistem = User::factory()->adminSistem()->create();
    $this->dinas = Dinas::factory()->create();
    $this->sekolah = Sekolah::factory()->forDinas($this->dinas)->create();
});

it('creates a user, assigns the role, and sends a password setup link', function () {
    Notification::fake();

    Livewire::actingAs($this->adminSistem)
        ->test(App\Livewire\Admin\Users\UserIndex::class)
        ->call('create')
        ->set('name', 'Budi Guru')
        ->set('email', 'budi@example.test')
        ->set('role', Role::Guru->value)
        ->set('sekolahId', $this->sekolah->id)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'budi@example.test')->firstOrFail();
    expect($user->isGuru())->toBeTrue()
        ->and($user->sekolah_id)->toBe($this->sekolah->id);

    $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'auditable_id' => $user->id]);
});

it('requires a supervisor type when the role is supervisor', function () {
    Livewire::actingAs($this->adminSistem)
        ->test(App\Livewire\Admin\Users\UserIndex::class)
        ->call('create')
        ->set('name', 'Sari Kepsek')
        ->set('email', 'sari@example.test')
        ->set('role', Role::Supervisor->value)
        ->set('sekolahId', $this->sekolah->id)
        ->call('save')
        ->assertHasErrors('supervisorType');
});

it('deactivates and reactivates a user with an audit trail', function () {
    $target = User::factory()->atSekolah($this->sekolah)->guru()->create();

    Livewire::actingAs($this->adminSistem)
        ->test(App\Livewire\Admin\Users\UserIndex::class)
        ->call('toggleActive', $target->id);

    expect($target->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated']);
});

it('does not let an admin deactivate their own account', function () {
    Livewire::actingAs($this->adminSistem)
        ->test(App\Livewire\Admin\Users\UserIndex::class)
        ->call('toggleActive', $this->adminSistem->id)
        ->assertHasErrors('form');

    expect($this->adminSistem->fresh()->is_active)->toBeTrue();
});

it('changing a user role writes a role.changed audit event', function () {
    $target = User::factory()->atSekolah($this->sekolah)->guru()->create();

    Livewire::actingAs($this->adminSistem)
        ->test(App\Livewire\Admin\Users\UserIndex::class)
        ->call('edit', $target->id)
        ->set('role', Role::Supervisor->value)
        ->set('supervisorType', SupervisorType::KepalaSekolah->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($target->fresh()->isSupervisor())->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['action' => 'role.changed']);
});
