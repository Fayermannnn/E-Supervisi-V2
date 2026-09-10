<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Notifications;

use App\Models\BestPractice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BestPracticeStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BestPractice $bestPractice,
        private readonly string $title,
        private readonly string $body,
        private readonly string $url,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'best_practice_id' => $this->bestPractice->getKey(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('Buka', url($this->url));
    }
}
