<?php

declare(strict_types=1);

namespace App\Livewire\Accountability;

use App\Domain\Accountability\Actions\CreateCalibrationSession;
use App\Models\CalibrationSession;
use App\Models\Instrument;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kalibrasi Antar-Penilai')]
class CalibrationIndex extends Component
{
    public bool $canManage = false;

    public bool $showForm = false;

    public string $judul = '';

    public string $deskripsi = '';

    public string $instrument_version_id = '';

    public string $artefak_url = '';

    public function mount(): void
    {
        $this->authorize('viewAny', CalibrationSession::class);
        $this->canManage = $this->user()->can(Permission::ManageCalibration->value);
    }

    public function create(CreateCalibrationSession $action): void
    {
        $data = $this->validate([
            'judul' => ['required', 'string', 'min:5', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'instrument_version_id' => ['required', 'uuid', 'exists:instrument_versions,id'],
            'artefak_url' => ['nullable', 'url', 'max:2000'],
        ]);

        try {
            $session = $action->handle($this->user(), [
                'instrument_version_id' => $data['instrument_version_id'],
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?: null,
                'artefak_url' => $data['artefak_url'] ?: null,
            ]);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('judul', $e->getMessage());

            return;
        }

        $this->redirectRoute('calibration.show', $session, navigate: true);
    }

    public function render(): View
    {
        $user = $this->user();

        $sessions = CalibrationSession::query()
            ->when($user->isAdminDinas(), fn ($q) => $q->where('dinas_id', $user->adminDinasId()))
            ->when(! $this->canManage, fn ($q) => $q->whereHas('participants', fn ($p) => $p->where('supervisor_id', $user->getKey())))
            ->withCount('participants')
            ->latest()
            ->get();

        $instruments = Instrument::query()->with('versions')->where('status', 'published')->get();

        return view('livewire.accountability.calibration-index', [
            'sessions' => $sessions,
            'instruments' => $instruments,
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
