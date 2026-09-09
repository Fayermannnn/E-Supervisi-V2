<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations;

use App\Domain\Audit\AuditLogger;
use App\Models\Dinas;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Struktur Organisasi')]
class OrganizationIndex extends Component
{
    public string $tab = 'sekolah';

    public bool $showDinasForm = false;

    public bool $showSekolahForm = false;

    public ?string $editingDinasId = null;

    public ?string $editingSekolahId = null;

    public string $dinasNama = '';

    public string $dinasKode = '';

    public string $dinasTipe = 'kabupaten';

    public string $dinasProvinsi = '';

    public string $sekolahNama = '';

    public string $sekolahNpsn = '';

    public string $sekolahJenjang = 'SD';

    public string $sekolahKecamatan = '';

    public string $sekolahWilayah = '';

    public ?string $sekolahDinasId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Sekolah::class);
    }

    public function newDinas(): void
    {
        $this->authorize('create', Dinas::class);
        $this->reset(['editingDinasId', 'dinasNama', 'dinasKode', 'dinasProvinsi']);
        $this->dinasTipe = 'kabupaten';
        $this->showDinasForm = true;
    }

    public function editDinas(string $id): void
    {
        $dinas = Dinas::query()->findOrFail($id);
        $this->authorize('update', $dinas);
        $this->editingDinasId = $dinas->id;
        $this->dinasNama = $dinas->nama;
        $this->dinasKode = $dinas->kode;
        $this->dinasTipe = $dinas->tipe;
        $this->dinasProvinsi = $dinas->provinsi;
        $this->showDinasForm = true;
    }

    public function saveDinas(AuditLogger $audit): void
    {
        $data = $this->validate([
            'dinasNama' => ['required', 'string', 'max:255'],
            'dinasKode' => ['required', 'string', 'max:20', Rule::unique('dinas', 'kode')->ignore($this->editingDinasId)],
            'dinasTipe' => ['required', Rule::in(['kabupaten', 'kota'])],
            'dinasProvinsi' => ['required', 'string', 'max:255'],
        ]);

        $attrs = [
            'nama' => $data['dinasNama'],
            'kode' => $data['dinasKode'],
            'tipe' => $data['dinasTipe'],
            'provinsi' => $data['dinasProvinsi'],
        ];

        if ($this->editingDinasId !== null) {
            $dinas = Dinas::query()->findOrFail($this->editingDinasId);
            $this->authorize('update', $dinas);
            $dinas->update($attrs);
            $audit->log('dinas.updated', $dinas, new: $attrs);
        } else {
            $this->authorize('create', Dinas::class);
            $dinas = Dinas::create($attrs);
            $audit->log('dinas.created', $dinas, new: $attrs);
        }

        $this->showDinasForm = false;
        $this->dispatch('notify', message: 'Dinas disimpan.');
    }

    public function newSekolah(): void
    {
        $this->authorize('create', Sekolah::class);
        $this->reset(['editingSekolahId', 'sekolahNama', 'sekolahNpsn', 'sekolahKecamatan', 'sekolahWilayah', 'sekolahDinasId']);
        $this->sekolahJenjang = 'SD';
        $this->showSekolahForm = true;
    }

    public function editSekolah(string $id): void
    {
        $sekolah = Sekolah::query()->findOrFail($id);
        $this->authorize('update', $sekolah);
        $this->editingSekolahId = $sekolah->id;
        $this->sekolahNama = $sekolah->nama;
        $this->sekolahNpsn = (string) $sekolah->npsn;
        $this->sekolahJenjang = $sekolah->jenjang;
        $this->sekolahKecamatan = (string) $sekolah->kecamatan;
        $this->sekolahWilayah = (string) $sekolah->wilayah;
        $this->sekolahDinasId = $sekolah->dinas_id;
        $this->showSekolahForm = true;
    }

    public function saveSekolah(AuditLogger $audit): void
    {
        $data = $this->validate([
            'sekolahNama' => ['required', 'string', 'max:255'],
            'sekolahNpsn' => ['nullable', 'string', 'max:20', Rule::unique('sekolah', 'npsn')->ignore($this->editingSekolahId)],
            'sekolahJenjang' => ['required', Rule::in(['PAUD', 'SD', 'SMP', 'SMA', 'SMK', 'SLB'])],
            'sekolahKecamatan' => ['nullable', 'string', 'max:255'],
            'sekolahWilayah' => ['nullable', 'string', 'max:255'],
            'sekolahDinasId' => ['required', 'uuid', Rule::exists('dinas', 'id')],
        ]);

        $attrs = [
            'nama' => $data['sekolahNama'],
            'npsn' => $data['sekolahNpsn'] ?: null,
            'jenjang' => $data['sekolahJenjang'],
            'kecamatan' => $data['sekolahKecamatan'] ?: null,
            'wilayah' => $data['sekolahWilayah'] ?: null,
            'dinas_id' => $data['sekolahDinasId'],
        ];

        if ($this->editingSekolahId !== null) {
            $sekolah = Sekolah::query()->findOrFail($this->editingSekolahId);
            $this->authorize('update', $sekolah);
            $sekolah->update($attrs);
            $audit->log('sekolah.updated', $sekolah, new: $attrs);
        } else {
            $this->authorize('create', Sekolah::class);
            // Admin Dinas hanya boleh menambah sekolah di dinasnya.
            $actor = $this->actor();
            if (! $actor->isAdminSistem() && $attrs['dinas_id'] !== $actor->adminDinasId()) {
                $this->addError('sekolahDinasId', 'Di luar lingkup dinas Anda.');

                return;
            }
            $sekolah = Sekolah::create($attrs);
            $audit->log('sekolah.created', $sekolah, new: $attrs);
        }

        $this->showSekolahForm = false;
        $this->dispatch('notify', message: 'Sekolah disimpan.');
    }

    public function render(): View
    {
        $actor = $this->actor();

        $dinasList = Dinas::query()
            ->when(! $actor->isAdminSistem(), fn ($q) => $q->whereKey($actor->adminDinasId()))
            ->withCount('sekolah')
            ->orderBy('nama')
            ->get();

        $sekolahList = Sekolah::query()
            ->when(! $actor->isAdminSistem(), fn ($q) => $q->where('dinas_id', $actor->adminDinasId()))
            ->with('dinas')
            ->orderBy('nama')
            ->paginate(15);

        return view('livewire.admin.organizations.organization-index', [
            'dinasList' => $dinasList,
            'sekolahList' => $sekolahList,
            'canManageDinas' => $actor->isAdminSistem(),
            'dinasOptions' => $dinasList,
        ]);
    }

    private function actor(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
