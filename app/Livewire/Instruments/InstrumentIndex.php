<?php

declare(strict_types=1);

namespace App\Livewire\Instruments;

use App\Domain\Audit\AuditLogger;
use App\Domain\Instruments\Enums\InstrumentStatus;
use App\Domain\Instruments\FormatBTemplate;
use App\Domain\Instruments\InstrumentSchema;
use App\Models\Instrument;
use App\Models\InstrumentVersion;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Bank Instrumen')]
class InstrumentIndex extends Component
{
    public ?string $previewId = null;

    public bool $showForm = false;

    public string $code = '';

    public string $nama = '';

    public string $deskripsi = '';

    public string $schemaJson = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Instrument::class);
    }

    public function preview(string $versionId): void
    {
        $this->previewId = $versionId;
    }

    public function create(): void
    {
        $this->authorize('create', Instrument::class);
        $this->reset(['code', 'nama', 'deskripsi']);
        $this->schemaJson = (string) json_encode(FormatBTemplate::schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->showForm = true;
    }

    public function save(AuditLogger $audit): void
    {
        $this->authorize('create', Instrument::class);

        $data = $this->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'schemaJson' => ['required', 'string'],
        ]);

        $decoded = json_decode($data['schemaJson'], true);
        if (! is_array($decoded)) {
            $this->addError('schemaJson', 'JSON tidak valid.');

            return;
        }

        $errors = InstrumentSchema::validate($decoded);
        if ($errors !== []) {
            $this->addError('schemaJson', implode(' ', $errors));

            return;
        }

        $actor = $this->user();
        $dinasId = $actor->isAdminSistem() ? null : $actor->adminDinasId();

        $instrument = Instrument::create([
            'code' => $data['code'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?: null,
            'pemilik_dinas_id' => $dinasId,
            'status' => InstrumentStatus::Published,
        ]);

        InstrumentVersion::create([
            'instrument_id' => $instrument->id,
            'version' => 1,
            'schema_json' => $decoded,
            'scoring_config' => $decoded['scoring_config'] ?? FormatBTemplate::scoringConfig(),
            'catatan_perubahan' => 'Versi awal.',
            'published_at' => now(),
            'created_by' => $actor->id,
        ]);

        $audit->log('instrument.created', $instrument, new: ['code' => $data['code']]);
        $this->showForm = false;
        $this->dispatch('notify', message: 'Instrumen dibuat & diterbitkan.');
    }

    public function render(): View
    {
        $user = $this->user();

        $instruments = Instrument::query()
            ->when(! $user->isAdminSistem(), fn ($q) => $q
                ->whereNull('pemilik_dinas_id')
                ->orWhere('pemilik_dinas_id', $user->resolveDinasId()))
            ->with(['versions' => fn ($q) => $q->orderByDesc('version')])
            ->orderBy('code')
            ->get();

        $previewVersion = $this->previewId === null
            ? null
            : InstrumentVersion::query()->whereKey($this->previewId)->first();

        return view('livewire.instruments.instrument-index', [
            'instruments' => $instruments,
            'previewSchema' => $previewVersion !== null ? $previewVersion->schema() : null,
            'canManage' => $user->can('create', Instrument::class),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
