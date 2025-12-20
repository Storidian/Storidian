<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VirtualFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VirtualFolder>
 */
class VirtualFolderFactory extends Factory
{
    protected $model = VirtualFolder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'tag_query' => [
                'include' => [fake()->word()],
                'operator' => 'AND',
            ],
            'sort_order' => 'created_at',
        ];
    }

    /**
     * Create a virtual folder for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set the include tags.
     */
    public function includeTags(array $tags, string $operator = 'AND'): static
    {
        return $this->state(fn (array $attributes) => [
            'tag_query' => array_merge($attributes['tag_query'] ?? [], [
                'include' => $tags,
                'operator' => $operator,
            ]),
        ]);
    }

    /**
     * Set the exclude tags.
     */
    public function excludeTags(array $tags): static
    {
        return $this->state(fn (array $attributes) => [
            'tag_query' => array_merge($attributes['tag_query'] ?? [], [
                'exclude' => $tags,
            ]),
        ]);
    }
}

