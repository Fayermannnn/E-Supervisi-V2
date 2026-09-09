<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Listeners;

use App\Domain\Supervision\Events\CycleTransitioned;
use App\Domain\Supervision\Notifications\CycleStatusNotification;
use App\Support\Enums\CycleStatus;
use Illuminate\Support\Facades\Notification;

class NotifyOnCycleTransition
{
    public function handle(CycleTransitioned $event): void
    {
        $cycle = $event->cycle;

        [$title, $body] = match ($event->to) {
            CycleStatus::Scheduled => [
                'Jadwal supervisi disepakati',
                "Observasi untuk siklus \"{$cycle->judul}\" telah dijadwalkan.",
            ],
            CycleStatus::ObservationDone => [
                'Observasi selesai',
                "Hasil observasi siklus \"{$cycle->judul}\" akan tersedia setelah dianalisis.",
            ],
            CycleStatus::Canceled => [
                'Siklus supervisi dibatalkan',
                "Siklus \"{$cycle->judul}\" dibatalkan. Alasan: ".($cycle->canceled_reason ?? '-'),
            ],
            default => [null, null],
        };

        if ($title !== null) {
            $guru = $cycle->guru()->first();

            if ($guru !== null) {
                Notification::send($guru, new CycleStatusNotification($cycle, $title, (string) $body));
            }
        }
    }
}
