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

arch('cycle-stage domains never depend on the Fase 4 professional-development layer')
    ->expect([
        'App\Domain\Planning',
        'App\Domain\Observation',
        'App\Domain\Analysis',
        'App\Domain\Feedback',
        'App\Domain\FollowUp',
        'App\Domain\Reporting',
    ])
    ->not->toUse([
        'App\Domain\Program',
        'App\Domain\ProfessionalDev',
        'App\Domain\Accountability',
    ]);

arch('the professional-development and accountability layers never trigger cycle transitions')
    ->expect(['App\Domain\ProfessionalDev', 'App\Domain\Accountability'])
    ->not->toUse([
        'App\Domain\Supervision\StateMachine\CycleStateMachine',
        'App\Models\CycleStatusTransition',
    ]);

arch('the AI layer stays out of the Fase 4 domains')
    ->expect('App\Domain\Ai')
    ->not->toUse([
        'App\Domain\Program',
        'App\Domain\ProfessionalDev',
        'App\Domain\Accountability',
    ]);
