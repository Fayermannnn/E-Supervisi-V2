<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Architecture guardrails
|--------------------------------------------------------------------------
|
| These encode the non-negotiable boundaries from docs/architecture.md and
| docs/domain-map.md. They grow as modules land in later phases.
|
*/

arch('no debug statements ship')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('application code declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('models extend the Eloquent base model')
    ->expect('App\Models')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\Concerns');

arch('the AI provider layer never touches the database (ADR-009)')
    ->expect('App\Domain\Ai\Providers')
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Eloquent\Model',
        'App\Models',
    ]);

arch('the AI contract is provider-agnostic')
    ->expect('App\Domain\Ai\Contracts')
    ->not->toUse(['App\Domain\Ai\Providers']);

arch('the state machine is the only writer of cycle status transitions')
    ->expect('App\Models\CycleStatusTransition')
    ->toOnlyBeUsedIn([
        'App\Domain\Supervision',
        'App\Domain\Reporting\Actions\CompileCycleReport',
        'App\Models',
    ]);
