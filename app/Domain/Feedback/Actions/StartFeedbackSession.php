<?php

declare(strict_types=1);

namespace App\Domain\Feedback\Actions;

use App\Models\FeedbackSession;
use App\Models\SupervisionCycle;
use App\Support\Enums\CycleStatus;
use DomainException;

class StartFeedbackSession
{
    /**
     * @throws DomainException
     */
    public function handle(SupervisionCycle $cycle): FeedbackSession
    {
        if (! in_array($cycle->status, [CycleStatus::AnalysisDone, CycleStatus::FeedbackGiven], true)) {
            throw new DomainException('Sesi umpan balik tersedia setelah analisis dikunci.');
        }

        return FeedbackSession::firstOrCreate(
            ['cycle_id' => $cycle->getKey()],
            ['status' => FeedbackSession::STATUS_BERLANGSUNG, 'dilaksanakan_at' => now()],
        );
    }
}
