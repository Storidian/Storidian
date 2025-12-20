<?php

namespace Database\Factories;

use App\Models\OauthClient;
use App\Models\OauthRefreshToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OauthRefreshToken>
 */
class OauthRefreshTokenFactory extends Factory
{
    protected $model = OauthRefreshToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Hash::make(Str::random(40)),
            'user_id' => User::factory(),
            'client_id' => OauthClient::factory(),
            'scopes' => ['profile', 'files:read'],
            'revoked' => false,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the token is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}

