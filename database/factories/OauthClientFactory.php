<?php

namespace Database\Factories;

use App\Models\OauthClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OauthClient>
 */
class OauthClientFactory extends Factory
{
    protected $model = OauthClient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' App',
            'client_id' => Str::random(32),
            'client_secret' => Str::random(64),
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => ['profile', 'files:read'],
            'is_first_party' => false,
            'is_public' => false,
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the client is first-party.
     */
    public function firstParty(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_first_party' => true,
        ]);
    }

    /**
     * Indicate that the client is public (no secret, PKCE required).
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'client_secret' => null,
        ]);
    }

    /**
     * Indicate the client was created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}

