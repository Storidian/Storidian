<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserApiKey>
 */
class UserApiKeyFactory extends Factory
{
    protected $model = UserApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = 'strd_u_' . Str::random(32);

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true) . ' Key',
            'key_hash' => Hash::make($key),
            'key_prefix' => substr($key, 0, 16),
            'scopes' => ['files:read', 'files:write'],
            'folder_scope' => null,
            'expires_at' => null,
            'last_used_at' => null,
        ];
    }

    /**
     * Create a key for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Scope the key to a specific folder.
     */
    public function scopedToFolder(Folder $folder): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_scope' => $folder->id,
            'user_id' => $folder->user_id,
        ]);
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
     * Create a read-only key.
     */
    public function readOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'scopes' => ['files:read'],
        ]);
    }
}

