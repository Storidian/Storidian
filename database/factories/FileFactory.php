<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word() . '.txt';

        return [
            'user_id' => User::factory(),
            'folder_id' => null,
            'name' => $name,
            'original_name' => $name,
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(100, 1000000),
            'storage_path' => Str::uuid() . '/' . date('Y/m') . '/' . Str::uuid() . '.txt',
            'storage_disk' => 'local',
            'checksum' => hash('sha256', Str::random(64)),
            'metadata' => null,
        ];
    }

    /**
     * Create a file for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a file in a specific folder.
     */
    public function inFolder(Folder $folder): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => $folder->id,
            'user_id' => $folder->user_id,
        ]);
    }

    /**
     * Create an image file.
     */
    public function image(): static
    {
        $name = fake()->word() . '.jpg';

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'original_name' => $name,
            'mime_type' => 'image/jpeg',
        ]);
    }

    /**
     * Create a PDF file.
     */
    public function pdf(): static
    {
        $name = fake()->word() . '.pdf';

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'original_name' => $name,
            'mime_type' => 'application/pdf',
        ]);
    }

    /**
     * Create a video file.
     */
    public function video(): static
    {
        $name = fake()->word() . '.mp4';

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'original_name' => $name,
            'mime_type' => 'video/mp4',
        ]);
    }
}

