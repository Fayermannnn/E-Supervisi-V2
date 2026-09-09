<?php

declare(strict_types=1);

namespace App\Livewire\Planning;

use App\Domain\Planning\Actions\RecordPlanningAgreementConsent;
use App\Domain\Planning\Actions\SavePlanningAgreement;
use App\Models\Instrument;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Perencanaan Supervisi')]
class PlanningEditor extends Component
{
    public SupervisionCycle $cycle;

    public string $fokusObservasi = '';

    public string $tujuan = '';

    public ?string $instrumentVersionId = null;

    public string $tipeObservasi = 'sinkron';

    public string $jadwalMulai = '';

    public string $jadwalSelesai = '';

    public string $kelas = '';

    public string $mataPelajaran = '';

    public string $lokasi = '';

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('schedule', $cycle);
        $this->cycle = $cycle;

        $a = $cycle->planningAgreement()->first();
        if ($a !== null) {
            $this->fokusObservasi = $a->fokus_observasi;
            $this->tujuan = (string) $a->tujuan;
            $this->instrumentVersionId = $a->instrument_version_id;
            $this->tipeObservasi = $a->tipe_observasi->value;
            $this->jadwalMulai = $a->jadwal_mulai->format('Y-m-d\TH:i');
            $this->jadwalSelesai = $a->jadwal_selesai?->format('Y-m-d\TH:i') ?? '';
            $this->kelas = (string) $a->kelas;
            $this->mataPelajaran = (string) $a->mata_pelajaran;
            $this->lokasi = (string) $a->lokasi;
        }
    }

    public function save(SavePlanningAgreement $action): void
    {
        $data = $this->validate([
            'fokusObservasi' => ['required', 'string', 'max:2000'],
            'tujuan' => ['nullable', 'string', 'max:2000'],
            'instrumentVersionId' => ['required', 'uuid', 'exists:instrument_versions,id'],
            'tipeObservasi' => ['required', Rule::in(['sinkron', 'asinkron'])],
            'jadwalMulai' => ['required', 'date'],
            'jadwalSelesai' => ['nullable', 'date', 'after:jadwalMulai'],
            'kelas' => ['nullable', 'string', 'max:255'],
            'mataPelajaran' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $action->handle($this->user(), $this->cycle, [
                'fokus_observasi' => $data['fokusObservasi'],
                'tujuan' => $data['tujuan'] ?: null,
                'instrument_version_id' => $data['instrumentVersionId'],
                'tipe_observasi' => $data['tipeObservasi'],
                'jadwal_mulai' => $data['jadwalMulai'],
                'jadwal_selesai' => $data['jadwalSelesai'] ?: null,
                'kelas' => $data['kelas'] ?: null,
                'mata_pelajaran' => $data['mataPelajaran'] ?: null,
                'lokasi' => $data['lokasi'] ?: null,
            ]);
        } catch (DomainException $e) {
            $this->addError('fokusObservasi', $e->getMessage());

            return;
        }

        $this->cycle->refresh();
        $this->dispatch('notify', message: 'Kesepakatan tersimpan. Menunggu persetujuan kedua pihak.');
    }

    public function consent(RecordPlanningAgreementConsent $action): mixed
    {
        $this->authorize('agree', $this->cycle);

        try {
            $action->handle($this->user(), $this->cycle);
        } catch (DomainException $e) {
            $this->addError('fokusObservasi', $e->getMessage());

            return null;
        }

        $this->cycle->refresh();

        if ($this->cycle->status->value >= \App\Support\Enums\CycleStatus::Scheduled->value) {
            return $this->redirectRoute('cycles.show', $this->cycle, navigate: true);
        }

        $this->dispatch('notify', message: 'Persetujuan Anda tercatat. Menunggu pihak lain.');

        return null;
    }

    public function render(): View
    {
        return view('livewire.planning.planning-editor', [
            'instruments' => Instrument::query()
                ->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('pemilik_dinas_id')->orWhere('pemilik_dinas_id', $this->cycle->dinas_id))
                ->with(['versions' => fn ($q) => $q->whereNotNull('published_at')->orderByDesc('version')])
                ->get(),
            'agreement' => $this->cycle->planningAgreement()->first(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
