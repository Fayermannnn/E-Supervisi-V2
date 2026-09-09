<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SyncObservationsRequest extends FormRequest
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
            'observations' => ['required', 'array', 'min:1', 'max:50'],
            'observations.*.id' => ['required', 'uuid'],
            'observations.*.cycle_id' => ['required', 'uuid'],
            'observations.*.base_version' => ['nullable', 'integer', 'min:1'],
            'observations.*.device_id' => ['nullable', 'string', 'max:100'],
            'observations.*.catatan_skrip' => ['nullable', 'string'],
            'observations.*.mulai_at' => ['nullable', 'date'],
            'observations.*.selesai_at' => ['nullable', 'date'],
            'observations.*.client_updated_at' => ['nullable', 'date'],
            'observations.*.responses' => ['array'],
            'observations.*.responses.*.item_key' => ['required', 'string', 'max:100'],
            'observations.*.responses.*.section_key' => ['required', 'string', 'max:100'],
            'observations.*.responses.*.value' => ['nullable'],
            'observations.*.responses.*.catatan_item' => ['nullable', 'string'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function batch(): array
    {
        $rows = $this->validated('observations');

        return is_array($rows) ? array_values($rows) : [];
    }
}
