<?php

declare(strict_types=1);

namespace App\Livewire\Analysis;

use App\Domain\Ai\Actions\ReviewAiGeneration;
use App\Domain\Analysis\Actions\FinalizeAnalysis;
use App\Domain\Analysis\Actions\PerformAnalysis;
use App\Domain\Analysis\Actions\RequestAnalysisAiDraft;
use App\Domain\Analysis\Actions\SaveAnalysisSummary;
use App\Models\AiGeneration;
use App\Models\AnalysisResult;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Analisis Hasil Observasi')]
class AnalysisWorkspace extends Component
{
    public SupervisionCycle $cycle;

    public string $ringkasan = '';

    public string $aiEdit = '';

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('view', $cycle);
        $this->cycle = $cycle;

        $result = $this->result();
        if ($result !== null) {
            $this->ringkasan = (string) $result->ringkasan;
        }
    }

    public function computeScores(PerformAnalysis $action): void
    {
        $this->guardSupervisor();
        try {
            $action->handle($this->user(), $this->cycle);
        } catch (DomainException $e) {
            $this->addError('analysis', $e->getMessage());

            return;
        }
        $this->dispatch('notify', message: 'Skor & temuan diperbarui.');
    }

    public function requestAiDraft(RequestAnalysisAiDraft $action): void
    {
        $result = $this->result();
        abort_if($result === null, 404);
        $this->authorize('requestAiDraft', $result);

        try {
            $action->handle($this->user(), $result);
        } catch (RuntimeException $e) {
            $this->addError('analysis', $e->getMessage());

            return;
        }
        $this->dispatch('notify', message: 'Permintaan draf AI dikirim. Segarkan sebentar lagi.');
    }

    public function reviewAi(string $decision, ReviewAiGeneration $review): void
    {
        $gen = $this->currentGeneration();
        abort_if($gen === null, 404);

        try {
            $review->handle($this->user(), $gen, $decision, $decision === 'edit' ? $this->aiEdit : null);
        } catch (Throwable $e) {
            $this->addError('analysis', $e->getMessage());

            return;
        }

        if ($decision === 'accept') {
            $this->ringkasan = (string) $gen->output;
        } elseif ($decision === 'edit') {
            $this->ringkasan = $this->aiEdit;
        }

        $this->dispatch('notify', message: 'Tinjauan draf AI dicatat. Simpan ringkasan untuk melanjutkan.');
    }

    public function saveSummary(SaveAnalysisSummary $action): void
    {
        $result = $this->result();
        abort_if($result === null, 404);
        $this->authorize('update', $result);

        $this->validate(['ringkasan' => ['required', 'string', 'min:30', 'max:20000']]);

        try {
            $action->handle($this->user(), $result, $this->ringkasan, $this->currentGeneration());
        } catch (DomainException $e) {
            $this->addError('ringkasan', $e->getMessage());

            return;
        }
        $this->dispatch('notify', message: 'Ringkasan tersimpan.');
    }

    public function finalize(FinalizeAnalysis $action): void
    {
        $result = $this->result();
        abort_if($result === null, 404);
        $this->authorize('finalize', $result);

        $result->refresh();

        try {
            $action->handle($this->user(), $result);
        } catch (Throwable $e) {
            $this->addError('analysis', $e->getMessage());

            return;
        }

        $this->cycle->refresh();
        $this->dispatch('notify', message: 'Analisis dikunci. Siklus lanjut ke umpan balik.');
        $this->redirectRoute('cycles.feedback', $this->cycle, navigate: true);
    }

    public function render(): View
    {
        $result = $this->result();

        return view('livewire.analysis.analysis-workspace', [
            'result' => $result,
            'findings' => $result?->findings()->get() ?? collect(),
            'generation' => $this->currentGeneration(),
            'observation' => $this->cycle->observations()->where('status', 'final')->latest('finalized_at')->first(),
        ]);
    }

    private function result(): ?AnalysisResult
    {
        return AnalysisResult::where('cycle_id', $this->cycle->id)->first();
    }

    private function currentGeneration(): ?AiGeneration
    {
        $id = $this->result()?->ai_generation_id;

        return $id === null ? null : AiGeneration::find($id);
    }

    private function guardSupervisor(): void
    {
        abort_unless($this->cycle->supervisor_id === $this->user()->getKey(), 403);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
