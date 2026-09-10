<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\SupervisionCycle;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Enums\CycleStatus;
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

        if ($user->isAhli()) {
            $panels = \App\Models\PanelExpert::where('user_id', $user->getKey());

            return [
                ['label' => 'Panel evaluasi', 'value' => (clone $panels)->count(), 'href' => route('evaluation.index')],
                ['label' => 'Penilaian terkirim', 'value' => (clone $panels)->whereHas('review', fn ($q) => $q->where('status', 'terkirim'))->count()],
                ['label' => 'Menunggu penilaian', 'value' => (clone $panels)->whereDoesntHave('review', fn ($q) => $q->where('status', 'terkirim'))->count(), 'tone' => 'warning'],
            ];
        }

        if ($user->isAdminDinas()) {
            $dinasId = $user->adminDinasId();
            $base = SupervisionCycle::query()->where('dinas_id', $dinasId);

            return [
                ['label' => 'Sekolah', 'value' => \App\Models\Sekolah::where('dinas_id', $dinasId)->count(), 'href' => route('admin.organizations.index')],
                ['label' => 'Siklus berjalan', 'value' => (clone $base)->whereNotIn('status', [CycleStatus::Archived->value, CycleStatus::Canceled->value])->count(), 'href' => route('cycles.index')],
                ['label' => 'Siklus dilaporkan', 'value' => (clone $base)->where('status', CycleStatus::Reported->value)->count()],
            ];
        }

        $scoped = SupervisionCycle::query()->visibleTo($user);

        return [
            ['label' => 'Siklus aktif', 'value' => (clone $scoped)->whereNotIn('status', [CycleStatus::Archived->value, CycleStatus::Canceled->value])->count(), 'href' => route('cycles.index')],
            ['label' => 'Menunggu observasi', 'value' => (clone $scoped)->where('status', CycleStatus::Scheduled->value)->count(), 'tone' => 'warning', 'href' => route('cycles.index', ['status' => CycleStatus::Scheduled->value])],
            ['label' => 'Perlu tindak lanjut', 'value' => (clone $scoped)->whereIn('status', [CycleStatus::FollowUpActive->value, CycleStatus::FollowUpOverdue->value])->count(), 'tone' => 'danger'],
        ];
    }
}
