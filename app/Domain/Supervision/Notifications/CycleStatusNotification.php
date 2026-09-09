<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Notifications;

use App\Models\SupervisionCycle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CycleStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SupervisionCycle $cycle,
        private readonly string $title,
        private readonly string $body,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && ($notifiable->notification_preferences['mail'] ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('cycles.show', $this->cycle),
            'cycle_id' => $this->cycle->getKey(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('Buka siklus', route('cycles.show', $this->cycle));
    }
}
