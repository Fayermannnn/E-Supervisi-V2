<?php

declare(strict_types=1);

namespace App\Livewire\Reporting;

use App\Domain\Reporting\Actions\CompileCycleReport;
use App\Domain\Reporting\Actions\RequestReportExport;
use App\Models\Report;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
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

    public function requestExport(RequestReportExport $action): void
    {
        $report = Report::where('scope', 'cycle')->where('scope_id', $this->cycle->id)->first();
        if ($report === null) {
            $this->addError('report', 'Susun laporan terlebih dahulu.');

            return;
        }

        try {
            $action->handle($this->user(), $report, 'pdf');
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('report', $e->getMessage());

            return;
        }

        $this->dispatch('notify', message: 'Ekspor PDF sedang disiapkan.');
    }

    public function render(): View
    {
        $report = Report::with(['snapshot', 'exports'])
            ->where('scope', 'cycle')->where('scope_id', $this->cycle->id)->first();

        return view('livewire.reporting.cycle-report', [
            'report' => $report,
            'snapshot' => $report?->snapshot?->data,
            'exports' => $report !== null ? $report->exports : collect(),
            'canCompile' => $this->cycle->supervisor_id === $this->user()->getKey(),
            'canExport' => $report !== null && \App\Domain\Reporting\ReportAccess::canExport($this->user(), $report),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
