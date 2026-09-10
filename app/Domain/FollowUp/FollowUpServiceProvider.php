<?php

declare(strict_types=1);

namespace App\Domain\FollowUp;

use App\Domain\FollowUp\Console\DetectOverdueFollowUpsCommand;
use App\Models\FollowUpPlan;
use App\Policies\FollowUpPlanPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FollowUpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(FollowUpPlan::class, FollowUpPlanPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([DetectOverdueFollowUpsCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(DetectOverdueFollowUpsCommand::class)->dailyAt('06:15')->withoutOverlapping();
        });
    }
}
