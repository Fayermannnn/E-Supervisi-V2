<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SaveAnnualProgramRequest extends FormRequest
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
            'judul' => ['required', 'string', 'max:255'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:ganjil,genap'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array{judul: string, tahun_ajaran: string, semester: string, catatan: string|null}
     */
    public function programData(): array
    {
        return [
            'judul' => (string) $this->string('judul'),
            'tahun_ajaran' => (string) $this->string('tahun_ajaran'),
            'semester' => (string) $this->string('semester'),
            'catatan' => $this->filled('catatan') ? (string) $this->string('catatan') : null,
        ];
    }
}
