<?php

declare(strict_types=1);

use App\Models\Dinas;
use App\Models\HelpArticle;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\Role;

use function Pest\Laravel\actingAs;

function userWithRole(Role $role): User
{
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();
    $user = User::factory()->atSekolah($sekolah)->create();
    $user->assignRole($role, $role === Role::AdminDinas ? $dinas : null);

    return $user;
}

it('renders the dashboard for each role', function (Role $role) {
    actingAs(userWithRole($role))->get('/dashboard')->assertOk();
})->with([
    'guru' => Role::Guru,
    'supervisor' => Role::Supervisor,
    'admin dinas' => Role::AdminDinas,
    'admin sistem' => Role::AdminSistem,
]);

it('renders the profile page', function () {
    actingAs(userWithRole(Role::Guru))->get('/profile')->assertOk()->assertSee('Profil Saya');
});

it('renders help pages', function () {
    $admin = userWithRole(Role::AdminSistem);
    $article = HelpArticle::create([
        'slug' => 'panduan', 'title' => 'Panduan', 'category' => 'umum',
        'body_markdown' => '## Judul', 'is_published' => true,
    ]);

    actingAs($admin)->get('/help')->assertOk();
    actingAs($admin)->get('/help/'.$article->slug)->assertOk()->assertSee('Panduan');
});

it('renders the support ticket page and accepts a submission', function () {
    Livewire::actingAs(userWithRole(Role::Guru))
        ->test(App\Livewire\Support\SupportTickets::class)
        ->set('subject', 'Tidak bisa masuk')
        ->set('message', 'Muncul pesan error saat login.')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('support_tickets', ['subject' => 'Tidak bisa masuk']);
});

it('renders every admin screen for admin sistem', function (string $route) {
    actingAs(userWithRole(Role::AdminSistem))->get(route($route))->assertOk();
})->with([
    'admin.users.index',
    'admin.organizations.index',
    'admin.assignments.index',
    'admin.policies.index',
    'admin.audit.index',
]);
