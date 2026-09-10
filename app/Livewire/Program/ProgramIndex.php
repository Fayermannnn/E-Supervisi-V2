<?php

declare(strict_types=1);

namespace App\Livewire\Program;

use App\Domain\Program\Actions\SaveAnnualProgram;
use App\Models\AnnualProgram;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Program Supervisi Tahunan')]
class ProgramIndex extends Component
{
    public bool $showForm = false;

    public string $judul = '';

    public string $tahun_ajaran = '';

    public string $semester = 'ganjil';

    public string $catatan = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AnnualProgram::class);
        $this->tahun_ajaran = (string) now()->year.'/'.(now()->year + 1);
    }

    public function save(SaveAnnualProgram $action): void
    {
        $data = $this->validate([
            'judul' => ['required', 'string', 'min:5', 'max:255'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:ganjil,genap'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $program = $action->handle($this->user(), null, [
                'judul' => $data['judul'],
                'tahun_ajaran' => $data['tahun_ajaran'],
                'semester' => $data['semester'],
                'catatan' => $data['catatan'] ?: null,
            ]);
        } catch (DomainException $e) {
            $this->addError('judul', $e->getMessage());

            return;
        }

        $this->redirectRoute('programs.show', $program, navigate: true);
    }

    public function render(): View
    {
        $programs = AnnualProgram::query()
            ->ownedBy($this->user())
            ->withCount('targets')
            ->latest()
            ->get();

        return view('livewire.program.program-index', ['programs' => $programs]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
