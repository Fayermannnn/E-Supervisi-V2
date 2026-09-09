<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dasbor')]
class Dashboard extends Component
{
    public function render(): mixed
    {
        $user = Auth::user();
        assert($user instanceof User);

        return view('livewire.dashboard.dashboard', [
            'user' => $user,
            'stats' => $this->statsFor($user),
        ]);
    }

    /**
     * @return array<int, array{label: string, value: int|string, tone?: string, href?: string}>
     */
    private function statsFor(User $user): array
    {
        if ($user->isAdminSistem()) {
            return [
                ['label' => 'Pengguna aktif', 'value' => User::where('is_active', true)->count(), 'href' => route('admin.users.index')],
                ['label' => 'Pengguna nonaktif', 'value' => User::where('is_active', false)->count(), 'href' => route('admin.users.index')],
                ['label' => 'Tiket terbuka', 'value' => SupportTicket::where('status', 'open')->count(), 'tone' => 'warning', 'href' => route('support.tickets')],
            ];
        }

        if ($user->isAdminDinas()) {
            $dinasId = $user->adminDinasId();

            return [
                ['label' => 'Sekolah', 'value' => \App\Models\Sekolah::where('dinas_id', $dinasId)->count(), 'href' => route('admin.organizations.index')],
                ['label' => 'Guru', 'value' => User::whereHas('sekolah', fn ($q) => $q->where('dinas_id', $dinasId))->count()],
                ['label' => 'Siklus pasca-observasi', 'value' => '—', 'tone' => 'default'],
            ];
        }

        // Guru / Supervisor — modul siklus hadir di Fase 2.
        return [
            ['label' => 'Siklus aktif', 'value' => '—'],
            ['label' => 'RTL mendekati tenggat', 'value' => '—', 'tone' => 'warning'],
            ['label' => 'RTL terlambat', 'value' => '—', 'tone' => 'danger'],
        ];
    }
}
