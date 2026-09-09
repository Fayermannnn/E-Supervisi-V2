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

// Domain-boundary arch rules (AI has no DB access, no cross-domain imports)
// are added in Phase 1 A3+ once each domain namespace contains classes.
