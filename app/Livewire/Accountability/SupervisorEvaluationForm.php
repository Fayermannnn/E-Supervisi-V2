<?php

declare(strict_types=1);

namespace App\Livewire\Accountability;

use App\Domain\Accountability\Actions\SubmitSupervisorEvaluation;
use App\Domain\Accountability\SupervisionProcessSurvey;
use App\Models\SupervisionCycle;
use App\Models\SupervisorEvaluation;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Penilaian Proses Supervisi')]
class SupervisorEvaluationForm extends Component
{
    public SupervisionCycle $cycle;

    /**
     * @var array<string, int>
     */
    public array $jawaban = [];

    public string $komentar = '';

    public bool $submitted = false;

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('submitForCycle', [SupervisorEvaluation::class, $cycle]);
        $this->cycle = $cycle;

        $existing = SupervisorEvaluation::where('cycle_id', $cycle->id)->first();
        if ($existing !== null) {
            $this->jawaban = $existing->jawaban;
            $this->komentar = (string) $existing->komentar;
            $this->submitted = true;
        } else {
            foreach (array_keys(SupervisionProcessSurvey::dimensions()) as $key) {
                $this->jawaban[$key] = 0;
            }
        }
    }

    public function submit(SubmitSupervisorEvaluation $action): void
    {
        $rules = ['komentar' => ['nullable', 'string', 'max:2000']];
        foreach (array_keys(SupervisionProcessSurvey::dimensions()) as $key) {
            $rules["jawaban.{$key}"] = ['required', 'integer', 'between:1,4'];
        }
        $this->validate($rules);

        try {
            $action->handle($this->user(), $this->cycle, $this->jawaban, $this->komentar ?: null);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('jawaban', $e->getMessage());

            return;
        }

        $this->submitted = true;
        $this->dispatch('notify', message: 'Penilaian tersimpan. Terima kasih.');
    }

    public function render(): View
    {
        return view('livewire.accountability.supervisor-evaluation-form', [
            'dimensions' => SupervisionProcessSurvey::dimensions(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
