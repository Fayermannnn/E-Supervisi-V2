<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Domain\Audit\AuditLogger;
use App\Domain\Support\Notifications\TicketOpenedNotification;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Enums\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Lapor Kendala')]
class SupportTickets extends Component
{
    public string $category = 'umum';

    public string $subject = '';

    public string $message = '';

    public function mount(): void
    {
        $this->authorize('create', SupportTicket::class);
    }

    public function submit(AuditLogger $audit): void
    {
        $data = $this->validate([
            'category' => ['required', Rule::in(['umum', 'akun', 'teknis', 'data'])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $this->actor()->id,
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        $audit->log('support_ticket.created', $ticket);

        $admins = User::whereHas('roleAssignments', fn ($q) => $q->where('role', Role::AdminSistem->value))->get();
        Notification::send($admins, new TicketOpenedNotification($ticket));

        $this->reset('subject', 'message');
        $this->category = 'umum';
        $this->dispatch('notify', message: 'Laporan terkirim. Tim akan menindaklanjuti.');
    }

    public function resolve(string $id, AuditLogger $audit): void
    {
        $ticket = SupportTicket::query()->findOrFail($id);
        $this->authorize('update', $ticket);
        $ticket->update(['status' => 'resolved', 'resolved_at' => now(), 'assigned_to' => $this->actor()->id]);
        $audit->log('support_ticket.resolved', $ticket);
        $this->dispatch('notify', message: 'Tiket ditandai selesai.');
    }

    public function render(): View
    {
        $actor = $this->actor();
        $canManage = $actor->can(\App\Support\Enums\Permission::ManageSupportTickets->value);

        $tickets = SupportTicket::query()
            ->with('user')
            ->when(! $canManage, fn ($q) => $q->where('user_id', $actor->id))
            ->latest()
            ->paginate(10);

        return view('livewire.support.support-tickets', [
            'tickets' => $tickets,
            'canManage' => $canManage,
        ]);
    }

    private function actor(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
