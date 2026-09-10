<?php

declare(strict_types=1);

use App\Domain\Evaluation\Actions\AssignExpertToPanel;
use App\Domain\Evaluation\Actions\CreateEvaluationPanel;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('renders the evaluation screens for researcher and expert', function () {
    $peneliti = User::factory()->adminSistem()->create();
    $panel = app(CreateEvaluationPanel::class)->handle($peneliti, [
        'judul' => 'Validasi Artefak', 'artefak_versi' => 'MVP Fase 1-5',
    ]);
    $expert = User::factory()->create(['email' => 'ahli.satu@e.test']);
    app(AssignExpertToPanel::class)->handle($peneliti, $panel, $expert, 'sistem_informasi');

    actingAs($peneliti)->get(route('evaluation.index'))->assertOk();
    actingAs($peneliti)->get(route('evaluation.show', $panel))->assertOk()->assertSee('Panel ahli');

    actingAs($expert)->get(route('evaluation.index'))->assertOk();
    actingAs($expert->refresh())->get(route('evaluation.review', $panel))->assertOk()->assertSee('SUS');
});

it('denies evaluation screens to a guru', function () {
    actingAs(User::factory()->guru()->create())->get(route('evaluation.index'))->assertForbidden();
});

it('renders the dashboard for an expert', function () {
    $expert = User::factory()->create();
    $expert->assignRole(App\Support\Enums\Role::Ahli);
    actingAs($expert)->get('/dashboard')->assertOk();
});
