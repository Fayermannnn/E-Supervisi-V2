<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisorAssignment>
 */
class SupervisorAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supervisor_id' => User::factory()->supervisor(),
            'guru_id' => User::factory()->guru(),
            'mulai' => now()->startOfYear()->toDateString(),
            'selesai' => null,
        ];
    }
}
