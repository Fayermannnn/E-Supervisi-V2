<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Policies;

use App\Domain\Administration\PolicySettings;
use App\Domain\Audit\AuditLogger;
use App\Models\Dinas;
use App\Models\PolicySetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Konfigurasi Kebijakan')]
class PolicyIndex extends Component
{
    public ?string $scopeDinasId = null;

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    public function mount(PolicySettings $settings): void
    {
        $this->authorize('viewAny', PolicySetting::class);

        $actor = $this->actor();
        if (! $actor->isAdminSistem()) {
            $this->scopeDinasId = $actor->adminDinasId();
        }

        $this->loadValues($settings);
    }

    public function updatedScopeDinasId(PolicySettings $settings): void
    {
        $this->loadValues($settings);
    }

    public function save(PolicySettings $settings, AuditLogger $audit): void
    {
        $actor = $this->actor();
        $dinasId = $actor->isAdminSistem() ? ($this->scopeDinasId ?: null) : $actor->adminDinasId();

        if (! $actor->isAdminSistem() && $dinasId === null) {
            abort(403);
        }

        foreach (PolicySettings::DEFINITIONS as $key => $def) {
            $value = $this->castValue($def['type'], $this->values[$key] ?? $def['default']);
            $settings->set($key, $value, $dinasId, $actor->id);
        }

        $audit->log('policy_settings.updated', context: ['dinas_id' => $dinasId, 'keys' => array_keys(PolicySettings::DEFINITIONS)]);
        $this->dispatch('notify', message: 'Kebijakan disimpan.');
    }

    private function loadValues(PolicySettings $settings): void
    {
        $dinasId = $this->scopeDinasId ?: null;

        foreach (PolicySettings::DEFINITIONS as $key => $def) {
            $value = $settings->get($key, $dinasId);
            $this->values[$key] = $def['type'] === 'list' && is_array($value)
                ? implode(',', $value)
                : $value;
        }
    }

    private function castValue(string $type, mixed $raw): mixed
    {
        return match ($type) {
            'boolean' => (bool) $raw,
            'integer' => (int) $raw,
            'list' => is_array($raw)
                ? array_values(array_filter(array_map('intval', $raw)))
                : array_values(array_filter(array_map('intval', explode(',', (string) $raw)))),
            default => $raw,
        };
    }

    public function render(): View
    {
        return view('livewire.admin.policies.policy-index', [
            'definitions' => PolicySettings::DEFINITIONS,
            'dinasOptions' => $this->actor()->isAdminSistem() ? Dinas::orderBy('nama')->get() : collect(),
        ]);
    }

    private function actor(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
