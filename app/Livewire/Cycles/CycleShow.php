<?php

declare(strict_types=1);

namespace App\Livewire\Cycles;

use App\Domain\Planning\Actions\RecordPlanningAgreementConsent;
use App\Domain\Planning\Actions\SubmitReflection;
use App\Domain\Supervision\Actions\CancelCycle;
use App\Models\SupervisionCycle;
use App\Models\TeacherReflection;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('components.layouts.app')]
#[Title('Siklus Supervisi')]
class CycleShow extends Component
{
    public SupervisionCycle $cycle;

    public string $reflectionContent = '';

    public string $cancelReason = '';

    public bool $showCancel = false;

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('view', $cycle);
        $this->cycle = $cycle;

        $existing = $cycle->reflections()->where('tahap', TeacherReflection::TAHAP_PRA)->first();
        $this->reflectionContent = $existing !== null ? $existing->konten : '';
    }

    public function consent(RecordPlanningAgreementConsent $action): void
    {
        $this->authorize('agree', $this->cycle);

        try {
            $action->handle($this->user(), $this->cycle);
        } catch (DomainException $e) {
            $this->addError('consent', $e->getMessage());

            return;
        }

        $this->cycle->refresh();
        $this->dispatch('notify', message: 'Persetujuan tercatat.');
    }

    public function submitReflection(SubmitReflection $action): void
    {
        $this->authorize('submitReflection', $this->cycle);

        $this->validate(['reflectionContent' => ['required', 'string', 'min:20', 'max:10000']]);

        $action->handle($this->user(), $this->cycle, TeacherReflection::TAHAP_PRA, $this->reflectionContent);
        $this->dispatch('notify', message: 'Refleksi tersimpan.');
    }

    public function cancel(CancelCycle $action): void
    {
        $this->authorize('cancel', $this->cycle);
        $this->validate(['cancelReason' => ['required', 'string', 'min:5', 'max:1000']]);

        try {
            $action->handle($this->user(), $this->cycle, $this->cancelReason);
        } catch (RuntimeException $e) {
            $this->addError('cancelReason', $e->getMessage());

            return;
        }

        $this->cycle->refresh();
        $this->showCancel = false;
        $this->dispatch('notify', message: 'Siklus dibatalkan.');
    }

    public function render(): View
    {
        $this->cycle->load([
            'guru', 'supervisor', 'planningAgreement.instrumentVersion.instrument',
            'observations' => fn ($q) => $q->latest(),
            'reflections',
        ]);

        return view('livewire.cycles.cycle-show', [
            'isGuru' => $this->cycle->guru_id === $this->user()->getKey(),
            'isSupervisor' => $this->cycle->supervisor_id === $this->user()->getKey(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
