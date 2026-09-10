<?php

declare(strict_types=1);

namespace App\Domain\FollowUp\Console;

use App\Domain\FollowUp\Actions\DetectOverdueFollowUps;
use Illuminate\Console\Command;

class DetectOverdueFollowUpsCommand extends Command
{
    protected $signature = 'esupervisi:detect-overdue-followups';

    protected $description = 'Tandai RTL yang lewat tenggat, eskalasi ke supervisor';

    public function handle(DetectOverdueFollowUps $action): int
    {
        $result = $action->handle();

        $this->info("RTL ditandai terlambat: {$result['marked_overdue']}, siklus pulih: {$result['recovered']}");

        return self::SUCCESS;
    }
}
