<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Domain\Notification\Console\DispatchDueRemindersCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([DispatchDueRemindersCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(DispatchDueRemindersCommand::class)->everyFiveMinutes()->withoutOverlapping();
        });
    }
}
