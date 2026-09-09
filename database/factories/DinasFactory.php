<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dinas;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Dinas>
 */
class DinasFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kota = fake()->city();

        return [
            'nama' => 'Dinas Pendidikan '.$kota,
            'kode' => strtoupper(Str::random(6)),
            'tipe' => fake()->randomElement(['kabupaten', 'kota']),
            'provinsi' => fake()->randomElement(['Kalimantan Timur', 'Jawa Barat', 'Sulawesi Selatan']),
        ];
    }
}
