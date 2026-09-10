<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Reporting\ReportAccess;
use App\Models\ReportExport;
use App\Models\User;

class ReportExportPolicy
{
    public function download(User $user, ReportExport $export): bool
    {
        return $export->isReady() && ReportAccess::canView($user, $export->report()->sole());
    }
}
