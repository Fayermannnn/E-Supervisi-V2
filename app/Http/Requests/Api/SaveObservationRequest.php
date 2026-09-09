<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SaveObservationRequest extends FormRequest
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
            'base_version' => ['sometimes', 'integer', 'min:1'],
            'catatan_skrip' => ['nullable', 'string'],
            'mulai_at' => ['nullable', 'date'],
            'selesai_at' => ['nullable', 'date'],
            'client_updated_at' => ['nullable', 'date'],
            'responses' => ['array'],
            'responses.*.item_key' => ['required', 'string', 'max:100'],
            'responses.*.section_key' => ['required', 'string', 'max:100'],
            'responses.*.value' => ['nullable'],
            'responses.*.catatan_item' => ['nullable', 'string'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function responsePayload(): array
    {
        $rows = $this->validated('responses', []);

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function metaPayload(): array
    {
        return array_filter([
            'catatan_skrip' => $this->input('catatan_skrip'),
            'mulai_at' => $this->input('mulai_at'),
            'selesai_at' => $this->input('selesai_at'),
            'client_updated_at' => $this->input('client_updated_at'),
        ], static fn ($v) => $v !== null);
    }
}
