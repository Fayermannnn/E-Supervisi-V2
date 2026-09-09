<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Actions\CreateUser;
use App\Domain\Identity\Actions\UpdateUser;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Manajemen Pengguna')]
class UserIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    // form fields
    public string $name = '';

    public string $email = '';

    public string $nip = '';

    public string $jabatan = '';

    public string $role = '';

    public string $supervisorType = '';

    public ?string $sekolahId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updating(string $field): void
    {
        if (in_array($field, ['search', 'roleFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->reset(['editingId', 'name', 'email', 'nip', 'jabatan', 'role', 'supervisorType', 'sekolahId']);
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $user = $this->scopedQuery()->findOrFail($id);
        $this->authorize('update', $user);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nip = (string) $user->nip;
        $this->jabatan = (string) $user->jabatan;
        $firstRole = $user->roles()->first();
        $this->role = $firstRole instanceof Role ? $firstRole->value : '';
        $this->supervisorType = $user->supervisor_type instanceof SupervisorType ? $user->supervisor_type->value : '';
        $this->sekolahId = $user->sekolah_id;
        $this->showForm = true;
    }

    public function save(CreateUser $createUser, UpdateUser $updateUser): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($this->editingId)],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::enum(Role::class)],
            'supervisorType' => [
                Rule::requiredIf($this->role === Role::Supervisor->value),
                'nullable',
                Rule::enum(SupervisorType::class),
            ],
            'sekolahId' => [
                Rule::requiredIf(in_array($this->role, [Role::Guru->value, Role::Supervisor->value], true)),
                'nullable',
                'uuid',
                Rule::exists('sekolah', 'id'),
            ],
        ]);

        $role = Role::from($data['role']);

        if ($this->editingId === null) {
            $this->authorize('create', User::class);
            $createUser->handle(
                actor: $this->actor(),
                name: $data['name'],
                email: $data['email'],
                role: $role,
                nip: $data['nip'] ?: null,
                jabatan: $data['jabatan'] ?: null,
                sekolahId: $data['sekolahId'] ?: null,
                supervisorType: $data['supervisorType'] ? SupervisorType::from($data['supervisorType']) : null,
            );
            $this->dispatch('notify', message: 'Pengguna dibuat. Tautan pengaturan kata sandi telah dikirim.');
        } else {
            $user = $this->scopedQuery()->findOrFail($this->editingId);
            $this->authorize('update', $user);
            $updateUser->handle(
                actor: $this->actor(),
                user: $user,
                name: $data['name'],
                email: $data['email'],
                role: $role,
                nip: $data['nip'] ?: null,
                jabatan: $data['jabatan'] ?: null,
                sekolahId: $data['sekolahId'] ?: null,
                supervisorType: $data['supervisorType'] ? SupervisorType::from($data['supervisorType']) : null,
            );
            $this->dispatch('notify', message: 'Pengguna diperbarui.');
        }

        $this->showForm = false;
    }

    public function toggleActive(string $id, AuditLogger $audit): void
    {
        $user = $this->scopedQuery()->findOrFail($id);
        $this->authorize('update', $user);

        if ($user->is($this->actor())) {
            $this->addError('form', 'Anda tidak dapat menonaktifkan akun sendiri.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        $audit->log($user->is_active ? 'user.activated' : 'user.deactivated', $user);
        $this->dispatch('notify', message: $user->is_active ? 'Pengguna diaktifkan.' : 'Pengguna dinonaktifkan.');
    }

    public function sendReset(string $id, AuditLogger $audit): void
    {
        $user = $this->scopedQuery()->findOrFail($id);
        $this->authorize('resetPassword', $user);

        Password::sendResetLink(['email' => $user->email]);
        $audit->log('user.password_reset_requested', $user);
        $this->dispatch('notify', message: 'Tautan atur ulang kata sandi dikirim ke '.$user->email);
    }

    public function render(): View
    {
        $users = $this->scopedQuery()
            ->when($this->search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'ilike', "%{$this->search}%")->orWhere('email', 'ilike', "%{$this->search}%")
            ))
            ->when($this->roleFilter !== '', fn ($q) => $q->whereHas('roleAssignments', fn ($q) => $q->where('role', $this->roleFilter)))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->with(['roleAssignments', 'sekolah'])
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.users.user-index', [
            'users' => $users,
            'roles' => Role::cases(),
            'supervisorTypes' => SupervisorType::cases(),
            'sekolahOptions' => $this->sekolahOptions(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    private function scopedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $actor = $this->actor();
        $query = User::query();

        if (! $actor->isAdminSistem()) {
            $dinasId = $actor->adminDinasId();
            $query->whereHas('sekolah', fn ($q) => $q->where('dinas_id', $dinasId));
        }

        return $query;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Sekolah>
     */
    private function sekolahOptions(): \Illuminate\Support\Collection
    {
        $actor = $this->actor();

        return Sekolah::query()
            ->when(! $actor->isAdminSistem(), fn ($q) => $q->where('dinas_id', $actor->adminDinasId()))
            ->orderBy('nama')
            ->get();
    }

    private function actor(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
