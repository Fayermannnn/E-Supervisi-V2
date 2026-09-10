<?php

declare(strict_types=1);

namespace App\Livewire\Evaluation;

use App\Domain\Evaluation\Actions\CreateEvaluationPanel;
use App\Models\EvaluationPanel;
use App\Models\PanelExpert;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Evaluasi Ahli')]
class PanelIndex extends Component
{
    public bool $canManage = false;

    public bool $showForm = false;

    public string $judul = '';

    public string $artefak_versi = '';

    public string $deskripsi = '';

    public function mount(): void
    {
        $this->authorize('viewAny', EvaluationPanel::class);
        $this->canManage = $this->user()->can(Permission::ManageEvaluationPanel->value);
        $this->artefak_versi = 'MVP Fase 1–5';
    }

    public function create(CreateEvaluationPanel $action): void
    {
        $data = $this->validate([
            'judul' => ['required', 'string', 'min:5', 'max:255'],
            'artefak_versi' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $panel = $action->handle($this->user(), [
                'judul' => $data['judul'],
                'artefak_versi' => $data['artefak_versi'],
                'deskripsi' => $data['deskripsi'] ?: null,
            ]);
        } catch (AuthorizationException $e) {
            $this->addError('judul', $e->getMessage());

            return;
        }

        $this->redirectRoute('evaluation.show', $panel, navigate: true);
    }

    public function render(): View
    {
        $user = $this->user();

        $panels = EvaluationPanel::query()
            ->when(
                ! $this->canManage,
                fn ($q) => $q->whereHas('experts', fn ($e) => $e->where('user_id', $user->getKey())),
            )
            ->withCount('experts')
            ->latest()
            ->get();

        $myReviews = $this->canManage ? collect() : PanelExpert::query()
            ->where('user_id', $user->getKey())
            ->with('review')
            ->get()
            ->keyBy('evaluation_panel_id');

        return view('livewire.evaluation.panel-index', ['panels' => $panels, 'myReviews' => $myReviews]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
