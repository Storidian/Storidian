<?php

namespace Database\Factories;

use App\Models\AuthProvider;
use App\Models\User;
use App\Models\UserAuthProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAuthProvider>
 */
class UserAuthProviderFactory extends Factory
{
    protected $model = UserAuthProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_id' => AuthProvider::factory(),
            'provider_user_id' => Str::random(32),
            'provider_data' => [
                'email' => fake()->email(),
                'name' => fake()->name(),
            ],
        ];
    }
}

