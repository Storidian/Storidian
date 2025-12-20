<?php

namespace Database\Factories;

use App\Models\SystemApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemApiKey>
 */
class SystemApiKeyFactory extends Factory
{
    protected $model = SystemApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = 'strd_s_' . Str::random(32);

        return [
            'created_by' => User::factory()->admin(),
            'name' => fake()->words(3, true) . ' Integration',
            'key_hash' => Hash::make($key),
            'key_prefix' => substr($key, 0, 16),
            'scopes' => ['files:read'],
            'allowed_users' => null,
            'expires_at' => null,
            'last_used_at' => null,
        ];
    }

    /**
     * Set an expiration date.
     */
    public function expiresAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $date,
        ]);
    }

    /**
     * Set the key as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Limit to specific users.
     */
    public function forUsers(array $userIds): static
    {
        return $this->state(fn (array $attributes) => [
            'allowed_users' => $userIds,
        ]);
    }
}

