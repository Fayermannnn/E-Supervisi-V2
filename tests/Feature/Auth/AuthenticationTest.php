<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Enums\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows the login screen to guests', function () {
    get('/login')->assertOk()->assertSee('Masuk');
});

it('redirects the app root to login for guests', function () {
    get('/')->assertRedirect('/login');
});

it('lets an active user authenticate via the Livewire form', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-kuat')]);
    $user->assignRole(Role::Guru);

    Livewire::test(App\Livewire\Auth\Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'rahasia-kuat')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    expect(auth()->check())->toBeTrue();
});

it('records last_login_at and an audit row on login', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-kuat')]);
    $user->assignRole(Role::Guru);

    Livewire::test(App\Livewire\Auth\Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'rahasia-kuat')
        ->call('login');

    expect($user->fresh()->last_login_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'actor_id' => $user->id]);
});

it('rejects a wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-kuat')]);
    $user->assignRole(Role::Guru);

    Livewire::test(App\Livewire\Auth\Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'salah')
        ->call('login')
        ->assertHasErrors('form.email');

    expect(auth()->check())->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['action' => 'auth.failed']);
});

it('blocks an inactive user even with correct credentials', function () {
    $user = User::factory()->inactive()->create(['password' => bcrypt('rahasia-kuat')]);
    $user->assignRole(Role::Guru);

    Livewire::test(App\Livewire\Auth\Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'rahasia-kuat')
        ->call('login')
        ->assertHasErrors('form.email');

    expect(auth()->check())->toBeFalse();
});

it('throttles after five failed attempts', function () {
    $user = User::factory()->create(['password' => bcrypt('rahasia-kuat')]);
    $user->assignRole(Role::Guru);

    $component = Livewire::test(App\Livewire\Auth\Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'salah');

    foreach (range(1, 5) as $i) {
        $component->call('login');
    }

    $component->call('login')->assertHasErrors('form.email');
    $this->assertDatabaseHas('audit_logs', ['action' => 'auth.lockout']);
});

it('logs the user out', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Guru);

    $this->actingAs($user);
    post('/logout')->assertRedirect('/login');

    expect(auth()->check())->toBeFalse();
});
