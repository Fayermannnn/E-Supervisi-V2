<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Administration\PolicySettings;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Unggah berkas bukti observasi (M2, Spec §8 POST /observations/{id}/media).
 *
 * Keamanan unggah (docs/testing.md — "upload .php/.svg berbahaya ditolak"):
 * `mimes:` Laravel memvalidasi via konten asli (fileinfo `guessExtension()`),
 * bukan ekstensi klien — jadi `shell.php` diganti nama `.jpg` tetap ditolak
 * karena kontennya tak ter-deteksi sebagai jpg. `.php`/`.phtml`/`.phar`, dst.
 * juga diblokir eksplisit oleh `shouldBlockPhpUpload()` bawaan Laravel
 * berdasarkan ekstensi asli klien. `.svg` sengaja TIDAK ada di whitelist
 * (mencegah SVG berisi <script> — stored XSS bila kelak disajikan inline).
 * `withValidator()` menambah lapis kedua: ekstensi hasil deteksi konten harus
 * sesuai kategori `tipe` yang diklaim (cegah "foto" berisi berkas lain).
 */
class UploadObservationMediaRequest extends FormRequest
{
    private const TYPE_EXTENSIONS = [
        'video' => ['mp4', 'webm', 'mov'],
        'audio' => ['m4a', 'mp3', 'wav'],
        'foto' => ['jpg', 'jpeg', 'png'],
        'dokumen' => ['pdf', 'docx'],
    ];

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');
            $tipe = $this->string('tipe')->toString();

            if (! $file instanceof UploadedFile || ! $file->isValid() || ! isset(self::TYPE_EXTENSIONS[$tipe])) {
                return;
            }

            $detected = strtolower((string) $file->guessExtension());

            if (! in_array($detected, self::TYPE_EXTENSIONS[$tipe], true)) {
                $validator->errors()->add(
                    'file',
                    "Isi berkas tidak sesuai jenis \"{$tipe}\" yang dipilih.",
                );
            }
        });
    }
}
