<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dinas;
use App\Models\Sekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sekolah>
 */
class SekolahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenjang = (string) fake()->randomElement(['SD', 'SMP', 'SMA', 'SMK']);

        return [
            'dinas_id' => Dinas::factory(),
            'nama' => $jenjang.' Negeri '.fake()->numberBetween(1, 30).' '.fake()->city(),
            'npsn' => (string) fake()->unique()->numerify('########'),
            'jenjang' => $jenjang,
            'kecamatan' => fake()->citySuffix().' '.fake()->firstName(),
            'wilayah' => fake()->randomElement(['Kota', 'Pinggiran', '3T']),
            'alamat' => fake()->address(),
        ];
    }

    public function forDinas(Dinas $dinas): static
    {
        return $this->state(fn (array $attributes): array => ['dinas_id' => $dinas->getKey()]);
    }
}
