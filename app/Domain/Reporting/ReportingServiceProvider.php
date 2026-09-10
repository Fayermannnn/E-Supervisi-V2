<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Reporting\Console\ArchiveReportedCyclesCommand;
use App\Models\ReportExport;
use App\Policies\ReportExportPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(ReportExport::class, ReportExportPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ArchiveReportedCyclesCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(ArchiveReportedCyclesCommand::class)->weeklyOn(1, '02:00')->withoutOverlapping();
        });
    }
}
