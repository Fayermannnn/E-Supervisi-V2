<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Assignments;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organization\Actions\AssignSupervisor;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\Role;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Penugasan Supervisor')]
class AssignmentIndex extends Component
{
    public bool $showForm = false;

    public ?string $supervisorId = null;

    public ?string $guruId = null;

    public string $mulai = '';

    public string $selesai = '';

    public function mount(): void
    {
        $this->authorize('viewAny', SupervisorAssignment::class);
        $this->mulai = now()->startOfYear()->toDateString();
    }

    public function create(): void
    {
        $this->authorize('create', SupervisorAssignment::class);
        $this->reset(['supervisorId', 'guruId', 'selesai']);
        $this->mulai = now()->startOfYear()->toDateString();
        $this->showForm = true;
    }

    public function save(AssignSupervisor $assign): void
    {
        $data = $this->validate([
            'supervisorId' => ['required', 'uuid', 'exists:users,id'],
            'guruId' => ['required', 'uuid', 'exists:users,id', 'different:supervisorId'],
            'mulai' => ['required', 'date'],
            'selesai' => ['nullable', 'date', 'after:mulai'],
        ]);

        $supervisor = User::query()->whereKey((string) $data['supervisorId'])->firstOrFail();
        $guru = User::query()->whereKey((string) $data['guruId'])->firstOrFail();

        if (! $supervisor->isSupervisor()) {
            throw ValidationException::withMessages(['supervisorId' => 'Pengguna bukan supervisor.']);
        }
        if (! $guru->isGuru()) {
            throw ValidationException::withMessages(['guruId' => 'Pengguna bukan guru.']);
        }

        $this->authorize('create', SupervisorAssignment::class);

        try {
            $assign->handle($this->actor(), $supervisor, $guru, $data['mulai'], $data['selesai'] ?: null);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['guruId' => $e->getMessage()]);
        }

        $this->showForm = false;
        $this->dispatch('notify', message: 'Penugasan dibuat.');
    }

    public function end(string $id, AuditLogger $audit): void
    {
        $assignment = SupervisorAssignment::query()->findOrFail($id);
        $this->authorize('update', $assignment);
        $assignment->update(['selesai' => now()->toDateString()]);
        $audit->log('supervisor_assignment.ended', $assignment);
        $this->dispatch('notify', message: 'Penugasan diakhiri.');
    }

    public function render(): View
    {
        $actor = $this->actor();
        $dinasId = $actor->isAdminSistem() ? null : $actor->adminDinasId();

        $assignments = SupervisorAssignment::query()
            ->with(['supervisor.sekolah', 'guru.sekolah'])
            ->when($dinasId !== null, fn ($q) => $q
                ->whereHas('supervisor.sekolah', fn ($q) => $q->where('dinas_id', $dinasId))
                ->whereHas('guru.sekolah', fn ($q) => $q->where('dinas_id', $dinasId)))
            ->latest('mulai')
            ->paginate(15);

        return view('livewire.admin.assignments.assignment-index', [
            'assignments' => $assignments,
            'supervisors' => $this->peopleWithRole(Role::Supervisor, $dinasId),
            'gurus' => $this->peopleWithRole(Role::Guru, $dinasId),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function peopleWithRole(Role $role, ?string $dinasId): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roleAssignments', fn ($q) => $q->where('role', $role->value))
            ->when($dinasId !== null, fn ($q) => $q->whereHas('sekolah', fn ($q) => $q->where('dinas_id', $dinasId)))
            ->with('sekolah')
            ->orderBy('name')
            ->get();
    }

    private function actor(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
