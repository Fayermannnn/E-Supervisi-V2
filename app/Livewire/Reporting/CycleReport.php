<?php

declare(strict_types=1);

namespace App\Livewire\Reporting;

use App\Domain\Reporting\Actions\CompileCycleReport;
use App\Models\Report;
use App\Models\SupervisionCycle;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Laporan Siklus')]
class CycleReport extends Component
{
    public SupervisionCycle $cycle;

    public string $overrideNote = '';

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('view', $cycle);
        $this->cycle = $cycle;
    }

    public function compile(CompileCycleReport $action): void
    {
        abort_unless($this->cycle->supervisor_id === $this->user()->getKey(), 403);

        try {
            $action->handle($this->user(), $this->cycle, $this->overrideNote ?: null);
        } catch (Throwable $e) {
            $this->addError('report', $e->getMessage());

            return;
        }

        $this->cycle->refresh();
        $this->dispatch('notify', message: 'Laporan siklus disusun.');
    }

    public function render(): View
    {
        $report = Report::with('snapshot')
            ->where('scope', 'cycle')->where('scope_id', $this->cycle->id)->first();

        return view('livewire.reporting.cycle-report', [
            'report' => $report,
            'snapshot' => $report?->snapshot?->data,
            'canCompile' => $this->cycle->supervisor_id === $this->user()->getKey(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
