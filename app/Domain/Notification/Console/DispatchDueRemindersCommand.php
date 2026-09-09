<?php

declare(strict_types=1);

namespace App\Domain\Notification\Console;

use App\Domain\Notification\DispatchesReminders;
use Illuminate\Console\Command;

class DispatchDueRemindersCommand extends Command
{
    protected $signature = 'esupervisi:dispatch-reminders';

    protected $description = 'Kirim pengingat E-Supervisi yang telah jatuh tempo';

    public function handle(DispatchesReminders $dispatcher): int
    {
        $count = $dispatcher->dispatchDue();

        $this->info("Pengingat terkirim: {$count}");

        return self::SUCCESS;
    }
}
