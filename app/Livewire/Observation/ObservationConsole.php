<?php

declare(strict_types=1);

namespace App\Livewire\Observation;

use App\Domain\Observation\Actions\FinalizeObservation;
use App\Domain\Observation\Actions\StartObservation;
use App\Models\Observation;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Konsol observasi — mobile-first & luring-mampu (ADR-006).
 *
 * Komponen Livewire hanya menyediakan data awal + finalisasi (butuh daring).
 * Pengisian & sinkronisasi ditangani modul JS `observation-console.js` yang
 * memakai IndexedDB + API /api/v1/sync/*.
 */
#[Layout('components.layouts.app')]
#[Title('Konsol Observasi')]
class ObservationConsole extends Component
{
    public SupervisionCycle $cycle;

    public ?string $observationId = null;

    public function mount(SupervisionCycle $cycle, StartObservation $starter): void
    {
        $this->authorize('observe', $cycle);
        $this->cycle = $cycle;

        if ($cycle->status === CycleStatus::Scheduled) {
            $draft = $cycle->observations()->where('status', 'draft')->latest()->first()
                ?? $starter->handle($this->user(), $cycle);

            $this->observationId = $draft->getKey();
        } elseif ($cycle->status === CycleStatus::ObservationDone) {
            $this->observationId = $cycle->observations()->where('status', 'final')->latest()->value('id');
        }
    }

    public function finalize(FinalizeObservation $action): void
    {
        $observation = Observation::query()->whereKey($this->observationId)->firstOrFail();
        $this->authorize('finalize', $observation);

        try {
            $result = $action->handle($this->user(), $observation);
        } catch (DomainException $e) {
            $this->addError('finalize', $e->getMessage());
            $this->dispatch('finalize-failed', message: $e->getMessage());

            return;
        }

        $this->cycle->refresh();
        $this->dispatch('notify', message: 'Observasi difinalkan. Siklus lanjut ke tahap analisis.');
        $this->redirectRoute('cycles.show', $this->cycle, navigate: true);
    }

    public function render(): View
    {
        $agreement = $this->cycle->planningAgreement()->with('instrumentVersion')->first();
        $version = $agreement?->instrumentVersion;

        $observation = $this->observationId === null
            ? null
            : Observation::query()->with('responses')->whereKey($this->observationId)->first();

        return view('livewire.observation.observation-console', [
            'schema' => $version !== null ? $version->schema_json : ['sections' => []],
            'instrumentVersionId' => $version?->getKey(),
            'observation' => $observation,
            'editable' => $this->cycle->status === CycleStatus::Scheduled,
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
