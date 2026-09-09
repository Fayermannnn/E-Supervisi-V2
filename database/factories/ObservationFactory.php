<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Instrument;
use App\Models\Observation;
use App\Models\SupervisionCycle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Observation>
 */
class ObservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'cycle_id' => SupervisionCycle::factory(),
            'observer_id' => fn (array $a) => SupervisionCycle::query()->whereKey($a['cycle_id'])->value('supervisor_id'),
            'instrument_version_id' => fn () => Instrument::factory()->published()->create()->versions()->firstOrFail()->id,
            'tipe' => 'sinkron',
            'mulai_at' => now(),
            'status' => 'draft',
            'version' => 1,
            'client_updated_at' => now(),
        ];
    }
}
