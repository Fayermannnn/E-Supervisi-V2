<?php

declare(strict_types=1);

namespace App\Domain\Supervision;

use App\Domain\Supervision\Events\CycleTransitioned;
use App\Domain\Supervision\Listeners\NotifyOnCycleTransition;
use App\Models\Observation;
use App\Models\SupervisionCycle;
use App\Policies\ObservationPolicy;
use App\Policies\SupervisionCyclePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SupervisionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SupervisionCycle::class, SupervisionCyclePolicy::class);
        Gate::policy(Observation::class, ObservationPolicy::class);

        Event::listen(CycleTransitioned::class, NotifyOnCycleTransition::class);
    }
}
