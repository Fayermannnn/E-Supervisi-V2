<?php

declare(strict_types=1);

namespace App\Livewire\ProfessionalDev;

use App\Domain\ProfessionalDev\Actions\GeneratePkbRecommendations;
use App\Domain\ProfessionalDev\Actions\NominateBestPractice;
use App\Domain\ProfessionalDev\Actions\RespondBestPracticeConsent;
use App\Domain\ProfessionalDev\Actions\RespondPkbRecommendation;
use App\Models\BestPractice;
use App\Models\PkbRecommendation;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('PKB & Praktik Baik')]
class CyclePkb extends Component
{
    public SupervisionCycle $cycle;

    public bool $showNominate = false;

    public string $bpJudul = '';

    public string $bpRingkasan = '';

    public string $bpPraktik = '';

    public string $bpTags = '';

    public bool $bpAnonim = false;

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('view', $cycle);
        $this->cycle = $cycle;
    }

    public function generate(GeneratePkbRecommendations $action): void
    {
        try {
            $result = $action->handle($this->user(), $this->cycle);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('pkb', $e->getMessage());

            return;
        }

        $this->dispatch('notify', message: "{$result['dibuat']} rekomendasi disusun.");
    }

    public function respond(string $id, string $status, RespondPkbRecommendation $action): void
    {
        try {
            $action->handle($this->user(), PkbRecommendation::findOrFail($id), $status);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('pkb', $e->getMessage());

            return;
        }

        $this->dispatch('notify', message: 'Rekomendasi diperbarui.');
    }

    public function nominate(NominateBestPractice $action): void
    {
        $data = $this->validate([
            'bpJudul' => ['required', 'string', 'min:5', 'max:255'],
            'bpRingkasan' => ['required', 'string', 'min:10', 'max:2000'],
            'bpPraktik' => ['required', 'string', 'min:20', 'max:20000'],
            'bpTags' => ['nullable', 'string', 'max:500'],
        ]);

        $tags = array_values(collect(explode(',', $data['bpTags'] ?? ''))->map(fn ($t): string => trim((string) $t))->filter()->all());

        try {
            $action->handle($this->user(), $this->cycle, [
                'judul' => $data['bpJudul'],
                'ringkasan' => $data['bpRingkasan'],
                'praktik' => $data['bpPraktik'],
                'tags' => $tags,
                'anonim' => $this->bpAnonim,
            ]);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('bpJudul', $e->getMessage());

            return;
        }

        $this->reset('showNominate', 'bpJudul', 'bpRingkasan', 'bpPraktik', 'bpTags', 'bpAnonim');
        $this->dispatch('notify', message: 'Nominasi dikirim. Menunggu persetujuan guru.');
    }

    public function respondConsent(bool $setuju, RespondBestPracticeConsent $action): void
    {
        $bp = BestPractice::where('cycle_id', $this->cycle->id)->firstOrFail();

        try {
            $action->handle($this->user(), $bp, $setuju);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('pkb', $e->getMessage());

            return;
        }

        $this->dispatch('notify', message: $setuju ? 'Persetujuan diberikan.' : 'Nominasi ditolak.');
    }

    public function render(): View
    {
        $user = $this->user();
        $recommendations = PkbRecommendation::with('catalogItem')
            ->where('cycle_id', $this->cycle->id)
            ->latest()
            ->get();

        $bestPractice = BestPractice::where('cycle_id', $this->cycle->id)->first();

        return view('livewire.professional-dev.cycle-pkb', [
            'recommendations' => $recommendations,
            'bestPractice' => $bestPractice,
            'isSupervisor' => $this->cycle->supervisor_id === $user->getKey(),
            'isGuru' => $this->cycle->guru_id === $user->getKey(),
            'canNominate' => $this->cycle->supervisor_id === $user->getKey()
                && in_array($this->cycle->status, [CycleStatus::Reported, CycleStatus::Archived], true),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
