<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi ditangani Policy di controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guru_id' => ['required', 'uuid', 'exists:users,id'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:ganjil,genap'],
            'judul' => ['required', 'string', 'max:255'],
            'fokus_ringkas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
