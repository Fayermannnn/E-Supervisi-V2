<?php

declare(strict_types=1);

namespace App\Livewire\Cycles;

use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Siklus Supervisi')]
class CycleIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', SupervisionCycle::class);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = $this->user();

        $cycles = SupervisionCycle::query()
            ->visibleTo($user)
            ->with(['guru', 'supervisor'])
            ->when($this->status !== '', fn ($q) => $q->where('status', (int) $this->status))
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('livewire.cycles.cycle-index', [
            'cycles' => $cycles,
            'statuses' => CycleStatus::cases(),
            'canCreate' => $user->can('create', SupervisionCycle::class),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
