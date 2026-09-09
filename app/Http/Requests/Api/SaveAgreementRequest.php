<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SaveAgreementRequest extends FormRequest
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
            'fokus_observasi' => ['required', 'string', 'max:2000'],
            'tujuan' => ['nullable', 'string', 'max:2000'],
            'instrument_version_id' => ['required', 'uuid', 'exists:instrument_versions,id'],
            'tipe_observasi' => ['required', 'in:sinkron,asinkron'],
            'jadwal_mulai' => ['required', 'date'],
            'jadwal_selesai' => ['nullable', 'date', 'after:jadwal_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', 'max:255'],
            'mata_pelajaran' => ['nullable', 'string', 'max:255'],
            'consent' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Data kesepakatan tanpa flag `consent`.
     *
     * @return array<string, mixed>
     */
    public function agreementData(): array
    {
        $data = $this->safe()->except(['consent']);

        return $data;
    }
}
