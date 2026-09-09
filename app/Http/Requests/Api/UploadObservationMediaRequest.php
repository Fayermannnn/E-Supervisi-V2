<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Administration\PolicySettings;
use Illuminate\Foundation\Http\FormRequest;

class UploadObservationMediaRequest extends FormRequest
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
        $maxMb = (int) app(PolicySettings::class)->get('observation.max_media_mb');

        return [
            'tipe' => ['required', 'in:video,audio,foto,dokumen'],
            'file' => [
                'required',
                'file',
                'max:'.($maxMb * 1024),
                'mimes:mp4,webm,mov,m4a,mp3,wav,jpg,jpeg,png,pdf,docx',
            ],
        ];
    }
}
