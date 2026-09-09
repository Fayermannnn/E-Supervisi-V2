<?php

declare(strict_types=1);

namespace App\Domain\Instruments;

use App\Models\Instrument;
use App\Policies\InstrumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class InstrumentsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Instrument::class, InstrumentPolicy::class);
    }
}
