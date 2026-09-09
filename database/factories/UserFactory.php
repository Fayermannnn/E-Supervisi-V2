<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'nip' => (string) fake()->unique()->numerify('9999########'),
            'jabatan' => 'Guru',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
            'sekolah_id' => null,
            'supervisor_type' => null,
            'notification_preferences' => null,
            'last_login_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    public function atSekolah(Sekolah $sekolah): static
    {
        return $this->state(fn (array $attributes): array => ['sekolah_id' => $sekolah->getKey()]);
    }

    public function guru(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Role::Guru));
    }

    public function supervisor(SupervisorType $type = SupervisorType::KepalaSekolah): static
    {
        return $this
            ->state(fn (array $attributes): array => [
                'supervisor_type' => $type->value,
                'jabatan' => $type->label(),
            ])
            ->afterCreating(fn (User $user) => $user->assignRole(Role::Supervisor));
    }

    public function adminSistem(): static
    {
        return $this
            ->state(fn (array $attributes): array => ['jabatan' => 'Administrator Sistem'])
            ->afterCreating(fn (User $user) => $user->assignRole(Role::AdminSistem));
    }
}
