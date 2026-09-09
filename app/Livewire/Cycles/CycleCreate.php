<?php

declare(strict_types=1);

namespace App\Livewire\Cycles;

use App\Domain\Supervision\Actions\CreateCycle;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buat Siklus Supervisi')]
class CycleCreate extends Component
{
    public ?string $guruId = null;

    public string $tahunAjaran = '';

    public string $semester = 'ganjil';

    public string $judul = '';

    public string $fokusRingkas = '';

    public function mount(): void
    {
        $this->authorize('create', SupervisionCycle::class);
        $this->tahunAjaran = now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
    }

    public function save(CreateCycle $action): mixed
    {
        $data = $this->validate([
            'guruId' => ['required', 'uuid', 'exists:users,id'],
            'tahunAjaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
            'judul' => ['required', 'string', 'max:255'],
            'fokusRingkas' => ['nullable', 'string', 'max:1000'],
        ]);

        $guru = User::query()->whereKey($data['guruId'])->firstOrFail();

        try {
            $cycle = $action->handle(
                $this->user(),
                $guru,
                $data['tahunAjaran'],
                $data['semester'],
                $data['judul'],
                $data['fokusRingkas'] ?: null,
            );
        } catch (DomainException $e) {
            $this->addError('guruId', $e->getMessage());

            return null;
        }

        return $this->redirectRoute('cycles.planning', $cycle, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.cycles.cycle-create', [
            'binaan' => $this->binaanOptions(),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function binaanOptions(): \Illuminate\Support\Collection
    {
        $supervisorId = $this->user()->getKey();
        $today = now()->toDateString();

        return User::query()
            ->where('is_active', true)
            ->whereHas('guruAssignments', fn ($q) => $q
                ->where('supervisor_id', $supervisorId)
                ->whereDate('mulai', '<=', $today)
                ->where(fn ($q) => $q->whereNull('selesai')->orWhereDate('selesai', '>=', $today)))
            ->with('sekolah')
            ->orderBy('name')
            ->get();
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
