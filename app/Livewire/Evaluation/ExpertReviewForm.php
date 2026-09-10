<?php

declare(strict_types=1);

namespace App\Livewire\Evaluation;

use App\Domain\Evaluation\Actions\SubmitExpertReview;
use App\Domain\Evaluation\ExpertJudgmentInstrument;
use App\Domain\Evaluation\UsabilityQuestionnaire;
use App\Models\EvaluationPanel;
use App\Models\PanelExpert;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Penilaian Ahli')]
class ExpertReviewForm extends Component
{
    public EvaluationPanel $panel;

    public PanelExpert $panelExpert;

    /**
     * @var array<string, string>
     */
    public array $relevansi = [];

    /**
     * @var array<string, int>
     */
    public array $kualitas = [];

    /**
     * @var array<string, int>
     */
    public array $sus = [];

    public string $catatan = '';

    public bool $submitted = false;

    public function mount(EvaluationPanel $panel): void
    {
        $this->authorize('review', $panel);
        $this->panel = $panel;

        $user = $this->user();
        $this->panelExpert = $panel->experts()->where('user_id', $user->getKey())->sole();

        $review = $this->panelExpert->review()->first();
        if ($review !== null) {
            $this->relevansi = $review->jawaban['relevansi'] ?? [];
            $this->kualitas = $review->jawaban['kualitas'] ?? [];
            $this->sus = $review->jawaban['sus'] ?? [];
            $this->catatan = (string) $review->catatan;
            $this->submitted = $review->isSubmitted();
        }
    }

    public function submit(SubmitExpertReview $action): void
    {
        $jawaban = ['relevansi' => $this->relevansi, 'kualitas' => $this->kualitas, 'sus' => $this->sus];

        if (! ExpertJudgmentInstrument::isComplete($jawaban)) {
            $this->addError('form', 'Seluruh aspek relevansi, kualitas, dan 10 butir usability wajib diisi.');

            return;
        }

        try {
            $action->handle($this->user(), $this->panelExpert, $jawaban, $this->catatan ?: null);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        $this->submitted = true;
        $this->dispatch('notify', message: 'Penilaian terkirim. Terima kasih.');
    }

    public function render(): View
    {
        return view('livewire.evaluation.expert-review-form', [
            'aspects' => ExpertJudgmentInstrument::aspects(),
            'relevansiOptions' => ExpertJudgmentInstrument::relevansiOptions(),
            'susItems' => UsabilityQuestionnaire::items(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
