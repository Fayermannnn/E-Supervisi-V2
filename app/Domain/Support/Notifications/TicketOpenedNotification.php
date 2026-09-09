<?php

declare(strict_types=1);

namespace App\Domain\Support\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketOpenedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SupportTicket $ticket) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Laporan kendala baru',
            'body' => $this->ticket->subject,
            'url' => route('support.tickets'),
            'ticket_id' => $this->ticket->id,
        ];
    }
}
