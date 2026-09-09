<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Models\ReminderSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Mengirim pengingat yang jatuh tempo (M14). Dipanggil oleh
 * DispatchDueRemindersCommand tiap 5 menit.
 */
class DispatchesReminders
{
    public function dispatchDue(): int
    {
        $sent = 0;

        ReminderSchedule::query()
            ->where('status', 'scheduled')
            ->where('send_at', '<=', now())
            ->with('user')
            ->chunkById(200, function ($reminders) use (&$sent): void {
                foreach ($reminders as $reminder) {
                    $this->send($reminder);
                    $sent++;
                }
            });

        return $sent;
    }

    private function send(ReminderSchedule $reminder): void
    {
        $user = $reminder->user;

        if (! $user instanceof User || ! $user->isActive()) {
            $reminder->forceFill(['status' => 'canceled'])->save();

            return;
        }

        $channels = $this->channelsFor($user, $reminder->channel);

        if ($channels !== []) {
            NotificationFacade::send($user, new GenericReminderNotification($reminder, $channels));
        }

        $reminder->forceFill(['status' => 'sent', 'sent_at' => now()])->save();
    }

    /**
     * @return list<string>
     */
    private function channelsFor(User $user, string $preferred): array
    {
        $prefs = $user->notification_preferences ?? [];
        $channels = [];

        if ($preferred === 'mail' && ($prefs['mail'] ?? true)) {
            $channels[] = 'mail';
        }

        if ($prefs['app'] ?? true) {
            $channels[] = 'database';
        }

        return $channels;
    }
}
