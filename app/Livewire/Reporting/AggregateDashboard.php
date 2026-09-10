<?php

declare(strict_types=1);

namespace App\Livewire\Reporting;

use App\Domain\Reporting\Actions\BuildAggregateReport;
use App\Models\Sekolah;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pelaporan Agregat')]
class AggregateDashboard extends Component
{
    #[Url]
    public string $tahunAjaran = '';

    #[Url]
    public string $wilayah = '';

    #[Url]
    public string $jenjang = '';

    public function mount(): void
    {
        abort_unless($this->user()->can(Permission::ViewAggregateReport->value), 403);
    }

    public function render(BuildAggregateReport $builder): View
    {
        $dinasId = $this->user()->adminDinasId();

        $data = $builder->handle($this->user(), array_filter([
            'tahun_ajaran' => $this->tahunAjaran ?: null,
            'wilayah' => $this->wilayah ?: null,
            'jenjang' => $this->jenjang ?: null,
        ]));

        return view('livewire.reporting.aggregate-dashboard', [
            'data' => $data,
            'tahunOptions' => SupervisionCycle::where('dinas_id', $dinasId)->distinct()->orderByDesc('tahun_ajaran')->pluck('tahun_ajaran'),
            'wilayahOptions' => Sekolah::where('dinas_id', $dinasId)->whereNotNull('wilayah')->distinct()->pluck('wilayah'),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
