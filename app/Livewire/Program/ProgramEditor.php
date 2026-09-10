<?php

declare(strict_types=1);

namespace App\Livewire\Program;

use App\Domain\Program\Actions\GenerateProgramCycles;
use App\Domain\Program\Actions\SetProgramStatus;
use App\Domain\Program\Actions\SyncProgramTargets;
use App\Models\AnnualProgram;
use App\Models\SupervisorAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Program Supervisi Tahunan')]
class ProgramEditor extends Component
{
    public AnnualProgram $program;

    /**
     * guru_id => bool (dipilih), fokus per guru.
     *
     * @var array<string, bool>
     */
    public array $selected = [];

    /**
     * @var array<string, string>
     */
    public array $fokus = [];

    public function mount(AnnualProgram $program): void
    {
        $this->authorize('view', $program);
        $this->program = $program;

        foreach ($program->targets()->get() as $target) {
            $this->selected[$target->guru_id] = true;
            $this->fokus[$target->guru_id] = (string) $target->fokus_ringkas;
        }
    }

    public function syncTargets(SyncProgramTargets $action): void
    {
        $this->authorize('update', $this->program);

        $targets = [];
        foreach ($this->selected as $guruId => $isOn) {
            if ($isOn) {
                $targets[] = [
                    'guru_id' => (string) $guruId,
                    'fokus_ringkas' => trim($this->fokus[$guruId] ?? '') ?: null,
                    'rencana_mulai' => null,
                    'rencana_selesai' => null,
                ];
            }
        }

        if ($targets === []) {
            $this->addError('selected', 'Pilih minimal satu guru.');

            return;
        }

        try {
            $action->handle($this->user(), $this->program, $targets);
        } catch (DomainException $e) {
            $this->addError('selected', $e->getMessage());

            return;
        }

        $this->program->refresh();
        $this->dispatch('notify', message: 'Daftar target diperbarui.');
    }

    public function generate(GenerateProgramCycles $action): void
    {
        $this->authorize('update', $this->program);

        try {
            $result = $action->handle($this->user(), $this->program);
        } catch (Throwable $e) {
            $this->addError('selected', $e->getMessage());

            return;
        }

        $this->program->refresh();
        $msg = "{$result['dibuat']} siklus dibuat.";
        if ($result['dilewati'] !== []) {
            $msg .= ' '.count($result['dilewati']).' dilewati.';
        }
        $this->dispatch('notify', message: $msg);
    }

    public function setStatus(string $status, SetProgramStatus $action): void
    {
        $this->authorize('update', $this->program);

        try {
            $action->handle($this->user(), $this->program, $status);
        } catch (DomainException $e) {
            $this->addError('selected', $e->getMessage());

            return;
        }

        $this->program->refresh();
    }

    public function render(): View
    {
        $binaan = SupervisorAssignment::query()
            ->with('guru.sekolah')
            ->where('supervisor_id', $this->user()->getKey())
            ->activeOn()
            ->get()
            ->pluck('guru')
            ->filter()
            ->unique('id')
            ->values();

        $targets = $this->program->targets()->with(['guru', 'cycle'])->get();

        return view('livewire.program.program-editor', [
            'binaan' => $binaan,
            'targets' => $targets,
            'pendingCount' => $targets->whereNull('cycle_id')->count(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
