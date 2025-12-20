<?php

namespace Database\Factories;

use App\Models\OauthAuthorizationCode;
use App\Models\OauthClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OauthAuthorizationCode>
 */
class OauthAuthorizationCodeFactory extends Factory
{
    protected $model = OauthAuthorizationCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::random(40),
            'user_id' => User::factory(),
            'client_id' => OauthClient::factory(),
            'scopes' => ['profile', 'files:read'],
            'redirect_uri' => 'https://example.com/callback',
            'code_challenge' => null,
            'code_challenge_method' => null,
            'revoked' => false,
            'expires_at' => now()->addMinutes(1),
        ];
    }

    /**
     * Indicate that the code uses PKCE.
     */
    public function withPkce(): static
    {
        return $this->state(fn (array $attributes) => [
            'code_challenge' => base64_encode(hash('sha256', Str::random(32), true)),
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Indicate that the code is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }

    /**
     * Indicate that the code is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}

