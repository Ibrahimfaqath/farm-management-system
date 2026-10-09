<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Worker,
            'farm_id' => null,
            'phone' => '08'.fake()->numerify('##########'),
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => Role::Owner]);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => Role::Manager]);
    }

    public function worker(): static
    {
        return $this->state(fn () => ['role' => Role::Worker]);
    }

    public function veterinarian(): static
    {
        return $this->state(fn () => ['role' => Role::Veterinarian]);
    }

    public function finance(): static
    {
        return $this->state(fn () => ['role' => Role::Finance]);
    }
}
