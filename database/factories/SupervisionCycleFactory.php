<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sekolah;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\SupervisorType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisionCycle>
 */
class SupervisionCycleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sekolah_id' => Sekolah::factory(),
            'guru_id' => fn (array $a): string => User::factory()->guru()->create(['sekolah_id' => $a['sekolah_id']])->id,
            'supervisor_id' => fn (array $a): string => User::factory()
                ->supervisor(SupervisorType::KepalaSekolah)
                ->create(['sekolah_id' => $a['sekolah_id']])->id,
            'dinas_id' => fn (array $a): ?string => Sekolah::query()->whereKey($a['sekolah_id'])->value('dinas_id'),
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'judul' => 'Supervisi '.fake()->randomElement(['Matematika', 'Bahasa Indonesia', 'IPA', 'IPS']),
            'fokus_ringkas' => 'Pengelolaan kelas dan aktivasi peserta didik',
            'status' => CycleStatus::Draft,
        ];
    }

    /**
     * Bangun siklus konsisten untuk pasangan supervisor–guru sekolah tertentu.
     */
    public function forPair(User $supervisor, User $guru): static
    {
        return $this->state(fn (array $attrs): array => [
            'supervisor_id' => $supervisor->id,
            'guru_id' => $guru->id,
            'sekolah_id' => $guru->sekolah_id,
            'dinas_id' => Sekolah::query()->whereKey($guru->sekolah_id)->value('dinas_id'),
        ]);
    }

    public function status(CycleStatus $status): static
    {
        return $this->state(fn (array $attrs): array => ['status' => $status]);
    }
}
