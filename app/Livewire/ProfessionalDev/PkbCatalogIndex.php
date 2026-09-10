<?php

declare(strict_types=1);

namespace App\Livewire\ProfessionalDev;

use App\Domain\ProfessionalDev\Actions\SavePkbCatalogItem;
use App\Domain\ProfessionalDev\Actions\SetPkbCatalogItemStatus;
use App\Models\PkbCatalogItem;
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
#[Title('Katalog PKB')]
class PkbCatalogIndex extends Component
{
    public bool $canManage = false;

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $judul = '';

    public string $deskripsi = '';

    public string $tipe = 'mandiri';

    public string $penyelenggara = '';

    public string $tautan = '';

    public string $tagsRaw = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PkbCatalogItem::class);
        $this->canManage = $this->user()->can(Permission::ManagePkbCatalog->value);
    }

    public function edit(string $id): void
    {
        $item = PkbCatalogItem::findOrFail($id);
        $this->authorize('update', $item);
        $this->editingId = $item->id;
        $this->judul = $item->judul;
        $this->deskripsi = $item->deskripsi;
        $this->tipe = $item->tipe;
        $this->penyelenggara = (string) $item->penyelenggara;
        $this->tautan = (string) $item->tautan;
        $this->tagsRaw = implode(', ', $item->tags ?? []);
        $this->showForm = true;
    }

    public function save(SavePkbCatalogItem $action): void
    {
        $data = $this->validate([
            'judul' => ['required', 'string', 'min:5', 'max:255'],
            'deskripsi' => ['required', 'string', 'min:10', 'max:5000'],
            'tipe' => ['required', 'in:pelatihan,mandiri,kkg,webinar,bacaan,lainnya'],
            'penyelenggara' => ['nullable', 'string', 'max:255'],
            'tautan' => ['nullable', 'url', 'max:2000'],
            'tagsRaw' => ['nullable', 'string', 'max:500'],
        ]);

        $tags = array_values(collect(explode(',', $data['tagsRaw'] ?? ''))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->all());

        $item = $this->editingId !== null ? PkbCatalogItem::findOrFail($this->editingId) : null;

        try {
            $action->handle($this->user(), $item, [
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'],
                'tipe' => $data['tipe'],
                'penyelenggara' => $data['penyelenggara'] ?: null,
                'tautan' => $data['tautan'] ?: null,
                'tags' => $tags,
            ]);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('judul', $e->getMessage());

            return;
        }

        $this->reset('showForm', 'editingId', 'judul', 'deskripsi', 'penyelenggara', 'tautan', 'tagsRaw');
        $this->tipe = 'mandiri';
        $this->dispatch('notify', message: 'Item katalog disimpan.');
    }

    public function setStatus(string $id, string $status, SetPkbCatalogItemStatus $action): void
    {
        try {
            $action->handle($this->user(), PkbCatalogItem::findOrFail($id), $status);
        } catch (AuthorizationException|DomainException $e) {
            $this->addError('judul', $e->getMessage());

            return;
        }

        $this->dispatch('notify', message: 'Status item diperbarui.');
    }

    public function render(): View
    {
        $user = $this->user();

        $query = PkbCatalogItem::query()->orderBy('judul');
        if ($this->canManage && ! $user->isAdminSistem()) {
            $query->where(fn ($q) => $q->whereNull('pemilik_dinas_id')->orWhere('pemilik_dinas_id', $user->adminDinasId()));
        } elseif (! $this->canManage) {
            $query->availableFor($user->resolveDinasId());
        }

        return view('livewire.professional-dev.pkb-catalog-index', ['items' => $query->get()]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
