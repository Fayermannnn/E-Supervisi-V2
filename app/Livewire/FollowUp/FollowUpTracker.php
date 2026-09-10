<?php

declare(strict_types=1);

namespace App\Livewire\FollowUp;

use App\Domain\FollowUp\Actions\CreateFollowUpPlan;
use App\Domain\FollowUp\Actions\SubmitFollowUpEvidence;
use App\Domain\FollowUp\Actions\UpdateFollowUpItem;
use App\Models\FollowUpItem;
use App\Models\FollowUpPlan;
use App\Models\SupervisionCycle;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Pelacak Tindak Lanjut')]
class FollowUpTracker extends Component
{
    public SupervisionCycle $cycle;

    public string $tujuan = '';

    public string $tenggat = '';

    /**
     * @var array<int, array{deskripsi: string, indikator: string}>
     */
    public array $items = [['deskripsi' => '', 'indikator' => '']];

    /**
     * Catatan bukti per item (id item => teks).
     *
     * @var array<string, string>
     */
    public array $evidenceNote = [];

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('view', $cycle);
        $this->cycle = $cycle;
        $this->tenggat = now()->addWeeks(4)->toDateString();
    }

    public function addItem(): void
    {
        $this->items[] = ['deskripsi' => '', 'indikator' => ''];
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
    }

    public function createPlan(CreateFollowUpPlan $action): void
    {
        $data = $this->validate([
            'tujuan' => ['required', 'string', 'min:10', 'max:2000'],
            'tenggat' => ['required', 'date', 'after:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.deskripsi' => ['required', 'string', 'max:1000'],
            'items.*.indikator' => ['required', 'string', 'max:1000'],
        ]);

        $items = array_values(array_map(
            static fn (array $i): array => [
                'deskripsi' => (string) $i['deskripsi'],
                'indikator_keberhasilan' => (string) $i['indikator'],
            ],
            $data['items'],
        ));

        try {
            $action->handle($this->user(), $this->cycle, (string) $data['tujuan'], (string) $data['tenggat'], $items);
        } catch (Throwable $e) {
            $this->addError('tujuan', $e->getMessage());

            return;
        }

        $this->reset('tujuan', 'items');
        $this->items = [['deskripsi' => '', 'indikator' => '']];
        $this->cycle->refresh();
        $this->dispatch('notify', message: 'RTL dibuat. Pengingat dijadwalkan.');
    }

    public function markItem(string $itemId, string $status, UpdateFollowUpItem $action): void
    {
        $item = FollowUpItem::findOrFail($itemId);
        try {
            $action->handle($this->user(), $item, $status);
        } catch (Throwable $e) {
            $this->addError('tujuan', $e->getMessage());

            return;
        }
        $this->cycle->refresh();
    }

    public function addEvidence(string $itemId, SubmitFollowUpEvidence $action): void
    {
        $item = FollowUpItem::findOrFail($itemId);
        $note = trim((string) ($this->evidenceNote[$itemId] ?? ''));
        if ($note === '') {
            return;
        }

        try {
            $action->handle($this->user(), $item, ['tipe' => 'catatan', 'deskripsi' => $note]);
        } catch (Throwable $e) {
            $this->addError('tujuan', $e->getMessage());

            return;
        }

        $this->evidenceNote[$itemId] = '';
        $this->dispatch('notify', message: 'Bukti tercatat.');
    }

    public function render(): View
    {
        $user = $this->user();
        $plans = FollowUpPlan::with(['items.evidence.item'])->where('cycle_id', $this->cycle->id)->latest()->get();

        return view('livewire.follow-up.follow-up-tracker', [
            'plans' => $plans,
            'isSupervisor' => $this->cycle->supervisor_id === $user->getKey(),
            'isGuru' => $this->cycle->guru_id === $user->getKey(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
