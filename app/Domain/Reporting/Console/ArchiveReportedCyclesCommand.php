<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Console;

use App\Domain\Reporting\Actions\ArchiveReportedCycles;
use Illuminate\Console\Command;

class ArchiveReportedCyclesCommand extends Command
{
    protected $signature = 'esupervisi:archive-cycles {--days=90}';

    protected $description = 'Arsipkan siklus yang sudah dilaporkan dan melewati ambang usia';

    public function handle(ArchiveReportedCycles $action): int
    {
        $count = $action->handle((int) $this->option('days'));

        $this->info("Siklus diarsipkan: {$count}");

        return self::SUCCESS;
    }
}
