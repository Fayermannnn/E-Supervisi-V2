<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fail loudly on lazy-loading, missing attributes, and mass-assignment
        // mistakes — cheap correctness insurance for a data-sensitive system.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Block destructive Artisan DB commands outside local (RULE 8).
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
