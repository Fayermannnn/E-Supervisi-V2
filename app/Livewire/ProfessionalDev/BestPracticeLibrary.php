<?php

declare(strict_types=1);

namespace App\Livewire\ProfessionalDev;

use App\Domain\ProfessionalDev\Actions\CurateBestPractice;
use App\Domain\ProfessionalDev\Actions\WithdrawBestPractice;
use App\Models\BestPractice;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Perpustakaan Praktik Baik')]
class BestPracticeLibrary extends Component
{
    public bool $canCurate = false;

    /**
     * @var array<string, string>
     */
    public array $catatan = [];

    public function mount(): void
    {
        $this->authorize('viewAny', BestPractice::class);
        $this->canCurate = $this->user()->can(Permission::CurateBestPractice->value);
    }

    public function curate(string $id, bool $terbit, CurateBestPractice $action): void
    {
        try {
            $action->handle($this->user(), BestPractice::findOrFail($id), $terbit, trim($this->catatan[$id] ?? '') ?: null);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('curate', $e->getMessage());

            return;
        }

        $this->dispatch('notify', message: $terbit ? 'Praktik baik diterbitkan.' : 'Nominasi ditolak.');
    }

    public function withdraw(string $id, WithdrawBestPractice $action): void
    {
        try {
            $action->handle($this->user(), BestPractice::findOrFail($id));
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('curate', $e->getMessage());

            return;
        }

        $this->dispatch('notify', message: 'Entri ditarik.');
    }

    public function render(): View
    {
        $user = $this->user();
        $dinasId = $user->resolveDinasId() ?? $user->adminDinasId();

        $published = $dinasId !== null
            ? BestPractice::query()->publishedInDinas($dinasId)->with('guru')->latest('terbit_at')->get()
            : collect();

        $queue = $this->canCurate && $user->adminDinasId() !== null
            ? BestPractice::query()
                ->where('dinas_id', $user->adminDinasId())
                ->where('status', BestPractice::STATUS_MENUNGGU_KURASI)
                ->with('guru')
                ->get()
            : collect();

        return view('livewire.professional-dev.best-practice-library', [
            'published' => $published,
            'queue' => $queue,
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
