<?php

declare(strict_types=1);

namespace App\Livewire\Notification;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Bell extends Component
{
    public bool $open = false;

    #[Computed]
    public function unreadCount(): int
    {
        return $this->user()?->unreadNotifications()->count() ?? 0;
    }

    /**
     * @return Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    #[Computed]
    public function recent(): Collection
    {
        $user = $this->user();

        if ($user === null) {
            return collect();
        }

        return $user->notifications()->latest()->limit(8)->get()->toBase();
    }

    public function markAllRead(): void
    {
        $this->user()?->unreadNotifications()->update(['read_at' => now()]);
        unset($this->unreadCount, $this->recent);
    }

    public function markRead(string $id): void
    {
        $this->user()?->notifications()->whereKey($id)->update(['read_at' => now()]);
        unset($this->unreadCount, $this->recent);
    }

    public function render(): View
    {
        return view('livewire.notification.bell');
    }

    private function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
