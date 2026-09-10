<?php

declare(strict_types=1);

namespace App\Livewire\Evaluation;

use App\Domain\Evaluation\Actions\AssignExpertToPanel;
use App\Domain\Evaluation\Actions\CloseEvaluationPanel;
use App\Domain\Evaluation\ExpertJudgmentInstrument;
use App\Models\EvaluationPanel;
use App\Models\PanelExpert;
use App\Models\User;
use App\Support\Enums\Permission;
use App\Support\Enums\Role;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Panel Evaluasi Ahli')]
class PanelShow extends Component
{
    public EvaluationPanel $panel;

    public string $expertEmail = '';

    public string $expertRumpun = PanelExpert::RUMPUN_MANAJEMEN;

    public string $expertAfiliasi = '';

    public bool $canManage = false;

    public function mount(EvaluationPanel $panel): void
    {
        $this->authorize('view', $panel);
        $this->panel = $panel;
        $this->canManage = $this->user()->can(Permission::ManageEvaluationPanel->value);
    }

    public function assignExpert(AssignExpertToPanel $action): void
    {
        $this->authorize('manage', $this->panel);

        $data = $this->validate([
            'expertEmail' => ['required', 'email', 'exists:users,email'],
            'expertRumpun' => ['required', 'in:manajemen_pendidikan,sistem_informasi,lainnya'],
            'expertAfiliasi' => ['nullable', 'string', 'max:255'],
        ]);

        $expert = User::where('email', $data['expertEmail'])->firstOrFail();

        try {
            $action->handle($this->user(), $this->panel, $expert, $data['expertRumpun'], $data['expertAfiliasi'] ?: null);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('expertEmail', $e->getMessage());

            return;
        }

        $this->reset('expertEmail', 'expertAfiliasi');
        $this->panel->refresh();
        $this->dispatch('notify', message: 'Ahli ditugaskan.');
    }

    public function close(CloseEvaluationPanel $action): void
    {
        $this->authorize('manage', $this->panel);

        try {
            $action->handle($this->user(), $this->panel);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('expertEmail', $e->getMessage());

            return;
        }

        $this->panel->refresh();
        $this->dispatch('notify', message: 'Panel ditutup. Statistik dihitung.');
    }

    public function render(): View
    {
        $experts = $this->panel->experts()->with(['user', 'review'])->get();

        $candidates = User::query()
            ->whereHas('roleAssignments', fn ($q) => $q->where('role', Role::Ahli->value))
            ->orderBy('name')
            ->get();

        return view('livewire.evaluation.panel-show', [
            'experts' => $experts,
            'candidates' => $candidates,
            'aspects' => ExpertJudgmentInstrument::aspects(),
            'submittedCount' => $experts->filter(fn ($e) => $e->review !== null && $e->review->isSubmitted())->count(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
