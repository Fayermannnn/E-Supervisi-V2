<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SyncProgramTargetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'targets' => ['required', 'array', 'min:1', 'max:200'],
            'targets.*.guru_id' => ['required', 'uuid', 'exists:users,id'],
            'targets.*.fokus_ringkas' => ['nullable', 'string', 'max:2000'],
            'targets.*.rencana_mulai' => ['nullable', 'date'],
            'targets.*.rencana_selesai' => ['nullable', 'date', 'after_or_equal:targets.*.rencana_mulai'],
        ];
    }

    /**
     * @return list<array{guru_id: string, fokus_ringkas: string|null, rencana_mulai: string|null, rencana_selesai: string|null}>
     */
    public function targets(): array
    {
        /** @var list<array<string, mixed>> $targets */
        $targets = $this->validated('targets');

        return array_map(static fn (array $t): array => [
            'guru_id' => (string) $t['guru_id'],
            'fokus_ringkas' => isset($t['fokus_ringkas']) ? (string) $t['fokus_ringkas'] : null,
            'rencana_mulai' => isset($t['rencana_mulai']) ? (string) $t['rencana_mulai'] : null,
            'rencana_selesai' => isset($t['rencana_selesai']) ? (string) $t['rencana_selesai'] : null,
        ], $targets);
    }
}
