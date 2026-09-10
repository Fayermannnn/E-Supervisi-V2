<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Reporting\Console\ArchiveReportedCyclesCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ArchiveReportedCyclesCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(ArchiveReportedCyclesCommand::class)->weeklyOn(1, '02:00')->withoutOverlapping();
        });
    }
}
