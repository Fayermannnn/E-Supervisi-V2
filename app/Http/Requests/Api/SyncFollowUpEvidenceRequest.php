<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SyncFollowUpEvidenceRequest extends FormRequest
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
            'evidence' => ['required', 'array', 'min:1', 'max:50'],
            'evidence.*.id' => ['nullable', 'uuid'],
            'evidence.*.follow_up_item_id' => ['required', 'uuid'],
            'evidence.*.tipe' => ['required', 'in:dokumen,foto,tautan,catatan'],
            'evidence.*.deskripsi' => ['nullable', 'string', 'max:2000'],
            'evidence.*.url' => ['nullable', 'url', 'max:2000'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function batch(): array
    {
        $rows = $this->validated('evidence');

        return is_array($rows) ? array_values($rows) : [];
    }
}
