<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Models\ReminderSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericReminderNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        private readonly ReminderSchedule $reminder,
        private readonly array $channels,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $payload = $this->reminder->payload ?? [];

        return [
            'kind' => $this->reminder->kind,
            'title' => $payload['title'] ?? 'Pengingat',
            'body' => $payload['body'] ?? '',
            'url' => $payload['url'] ?? null,
            'remindable_type' => $this->reminder->remindable_type,
            'remindable_id' => $this->reminder->remindable_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->reminder->payload ?? [];

        $mail = (new MailMessage)
            ->subject($payload['title'] ?? 'Pengingat E-Supervisi')
            ->line($payload['body'] ?? 'Anda memiliki pengingat baru.');

        if (isset($payload['url'])) {
            $mail->action('Buka', $payload['url']);
        }

        return $mail;
    }
}
