<?php

namespace Database\Factories;

use App\Models\ImportToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ImportToken>
 */
class ImportTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'token_hash' => hash('sha256', Str::random(64)),
            'abilities' => ['news:import'],
            'created_by' => User::factory()->admin(),
            'user_id' => User::factory()->admin(),
            'expires_at' => now()->addDays(90),
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function publish(): static
    {
        return $this->state(fn (): array => [
            'abilities' => ['news:import', 'news:publish'],
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'revoked_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
