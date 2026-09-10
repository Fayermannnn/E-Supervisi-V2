<?php

declare(strict_types=1);

namespace App\Domain\FollowUp\Notifications;

use App\Models\SupervisionCycle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowUpEscalationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SupervisionCycle $cycle) {}

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
            'title' => 'RTL melewati tenggat',
            'body' => "Rencana tindak lanjut pada siklus \"{$this->cycle->judul}\" telah melewati tenggat dan perlu perhatian Anda.",
            'url' => route('cycles.show', $this->cycle),
            'cycle_id' => $this->cycle->getKey(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('RTL melewati tenggat — '.$this->cycle->judul)
            ->line('Rencana tindak lanjut pada siklus ini telah melewati tenggat.')
            ->action('Tinjau siklus', route('cycles.show', $this->cycle));
    }
}
