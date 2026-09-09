<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReflectionRequest extends FormRequest
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
            'tahap' => ['required', 'in:pra_observasi,pasca_umpan_balik'],
            'konten' => ['required', 'string', 'max:10000'],
        ];
    }
}
