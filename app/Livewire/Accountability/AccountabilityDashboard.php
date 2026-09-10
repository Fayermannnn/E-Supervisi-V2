<?php

declare(strict_types=1);

namespace App\Livewire\Accountability;

use App\Domain\Accountability\AccountabilityAggregator;
use App\Domain\Accountability\SupervisionProcessSurvey;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Akuntabilitas Supervisor')]
class AccountabilityDashboard extends Component
{
    public function mount(): void
    {
        abort_unless($this->user()->can(Permission::ViewAccountabilityReport->value), 403);
    }

    public function render(AccountabilityAggregator $aggregator): View
    {
        $user = $this->user();

        $data = $user->isAdminDinas() && $user->adminDinasId() !== null
            ? $aggregator->forDinas($user->adminDinasId())
            : $aggregator->forSupervisor($user);

        return view('livewire.accountability.accountability-dashboard', [
            'data' => $data,
            'dimensions' => SupervisionProcessSurvey::dimensions(),
            'isAdminDinas' => $user->isAdminDinas(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
