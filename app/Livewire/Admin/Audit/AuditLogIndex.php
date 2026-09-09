<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Audit Log')]
class AuditLogIndex extends Component
{
    use WithPagination;

    public string $actionFilter = '';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updating(string $field): void
    {
        if (in_array($field, ['actionFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $actor = $this->actor();

        $query = AuditLog::query()->with('actor')->latest('created_at');

        if (! $actor->isAdminSistem()) {
            // Admin Dinas: hanya baris yang aktornya berada di dinasnya.
            $dinasId = $actor->resolveDinasId();
            $query->whereHas('actor', function ($q) use ($dinasId): void {
                $q->whereHas('sekolah', fn ($q) => $q->where('dinas_id', $dinasId))
                    ->orWhereHas('roleAssignments', fn ($q) => $q->where('dinas_id', $dinasId));
            });
        }

        $query->when($this->actionFilter !== '', fn ($q) => $q->where('action', 'like', $this->actionFilter.'%'))
            ->when($this->search !== '', fn ($q) => $q->where('action', 'ilike', "%{$this->search}%"));

        return view('livewire.admin.audit.audit-log-index', [
            'logs' => $query->paginate(25),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    private function actor(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
