<?php

namespace Database\Factories;

use App\Models\AuthProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuthProvider>
 */
class AuthProviderFactory extends Factory
{
    protected $model = AuthProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company() . ' SSO';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'provider_class' => 'socialite',
            'config' => [
                'client_id' => Str::random(32),
                'client_secret' => Str::random(64),
            ],
            'enabled' => true,
            'allow_registration' => false,
            'trust_email' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the provider is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    /**
     * Indicate that the provider allows registration.
     */
    public function allowsRegistration(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_registration' => true,
        ]);
    }

    /**
     * Indicate that the provider trusts email.
     */
    public function trustsEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'trust_email' => true,
        ]);
    }

    /**
     * Create a Google provider.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Google',
            'slug' => 'google',
            'provider_class' => 'google',
        ]);
    }

    /**
     * Create an OIDC provider.
     */
    public function oidc(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'OIDC',
            'slug' => 'oidc',
            'provider_class' => 'oidc',
            'config' => [
                'client_id' => Str::random(32),
                'client_secret' => Str::random(64),
                'issuer' => 'https://auth.example.com',
            ],
        ]);
    }
}

