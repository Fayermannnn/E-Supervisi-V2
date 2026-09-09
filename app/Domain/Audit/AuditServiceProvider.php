<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\Audit\Listeners\LogAuthenticationEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
    }

    public function boot(): void
    {
        Event::subscribe(LogAuthenticationEvents::class);
    }
}
