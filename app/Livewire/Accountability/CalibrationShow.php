<?php

declare(strict_types=1);

namespace App\Livewire\Accountability;

use App\Domain\Accountability\Actions\AddCalibrationParticipant;
use App\Domain\Accountability\Actions\CloseCalibrationSession;
use App\Domain\Accountability\Actions\SubmitCalibrationScores;
use App\Models\CalibrationSession;
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
#[Title('Sesi Kalibrasi')]
class CalibrationShow extends Component
{
    public CalibrationSession $session;

    public string $addSupervisorId = '';

    /**
     * @var array<string, mixed>
     */
    public array $scores = [];

    public function mount(CalibrationSession $session): void
    {
        $this->authorize('view', $session);
        $this->session = $session;

        $mine = $session->participants()->where('supervisor_id', $this->user()->getKey())->first();
        if ($mine !== null) {
            foreach ($mine->scores()->get() as $s) {
                $this->scores[$s->item_key] = $s->nilai;
            }
        }
    }

    public function addParticipant(AddCalibrationParticipant $action): void
    {
        $this->authorize('manage', $this->session);
        $this->validate(['addSupervisorId' => ['required', 'uuid', 'exists:users,id']]);

        try {
            $action->handle($this->user(), $this->session, User::query()->findOrFail($this->addSupervisorId));
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('addSupervisorId', $e->getMessage());

            return;
        }

        $this->reset('addSupervisorId');
        $this->session->refresh();
        $this->dispatch('notify', message: 'Penilai ditambahkan.');
    }

    public function submitScores(SubmitCalibrationScores $action): void
    {
        $this->authorize('score', $this->session);

        $clean = [];
        foreach ($this->scores as $key => $val) {
            if ($val !== '' && $val !== null) {
                $clean[$key] = (float) $val;
            }
        }

        try {
            $action->handle($this->user(), $this->session, $clean);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('scores', $e->getMessage());

            return;
        }

        $this->session->refresh();
        $this->dispatch('notify', message: 'Skor Anda terkirim.');
    }

    public function close(CloseCalibrationSession $action): void
    {
        $this->authorize('manage', $this->session);

        try {
            $action->handle($this->user(), $this->session);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('scores', $e->getMessage());

            return;
        }

        $this->session->refresh();
        $this->dispatch('notify', message: 'Sesi ditutup. Statistik dihitung.');
    }

    public function render(): View
    {
        $user = $this->user();
        $schema = $this->session->instrumentVersion()->sole()->schema();

        $scorableItems = [];
        foreach ($schema->sections as $section) {
            foreach ($section->items as $item) {
                if ($item->type->isScorable()) {
                    $scorableItems[] = ['section' => $section->title, 'key' => $item->key, 'label' => $item->label];
                }
            }
        }

        $candidates = User::query()
            ->whereHas('roleAssignments', fn ($q) => $q->where('role', Role::Supervisor->value))
            ->when($user->adminDinasId() !== null, fn ($q) => $q->whereHas('sekolah', fn ($s) => $s->where('dinas_id', $user->adminDinasId())))
            ->orderBy('name')
            ->get();

        return view('livewire.accountability.calibration-show', [
            'items' => $scorableItems,
            'participants' => $this->session->participants()->with('supervisor')->get(),
            'candidates' => $candidates,
            'canManage' => $user->can(Permission::ManageCalibration->value) && ($user->isAdminSistem() || $user->adminDinasId() === $this->session->dinas_id),
            'isParticipant' => $this->session->participants()->where('supervisor_id', $user->getKey())->exists(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
