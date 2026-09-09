<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Instruments\Enums\InstrumentStatus;
use App\Domain\Instruments\FormatBTemplate;
use App\Models\Instrument;
use App\Models\InstrumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instrument>
 */
class InstrumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'B',
            'nama' => 'Format B — Observasi Pelaksanaan Pembelajaran (CONTOH)',
            'deskripsi' => 'Instrumen contoh untuk demo; belum tervalidasi.',
            'pemilik_dinas_id' => null,
            'status' => InstrumentStatus::Published,
        ];
    }

    public function published(): static
    {
        return $this->afterCreating(function (Instrument $instrument): void {
            InstrumentVersion::create([
                'instrument_id' => $instrument->id,
                'version' => 1,
                'schema_json' => FormatBTemplate::schema(),
                'scoring_config' => FormatBTemplate::scoringConfig(),
                'catatan_perubahan' => 'Versi awal (contoh).',
                'published_at' => now(),
            ]);
        });
    }
}
