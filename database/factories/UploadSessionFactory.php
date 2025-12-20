<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Folder;
use App\Models\UploadSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UploadSession>
 */
class UploadSessionFactory extends Factory
{
    protected $model = UploadSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word() . '.' . fake()->fileExtension();

        return [
            'upload_id' => Str::random(32), // tusd generates 32-char hex IDs
            'user_id' => User::factory(),
            'folder_id' => null,
            'filename' => $filename,
            'filesize' => fake()->numberBetween(1000, 100000000), // 1KB - 100MB
            'filetype' => fake()->mimeType(),
            'status' => UploadSession::STATUS_PENDING,
            'file_id' => null,
        ];
    }

    /**
     * Create an upload session for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create an upload session for a specific folder.
     */
    public function inFolder(Folder $folder): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => $folder->id,
            'user_id' => $folder->user_id,
        ]);
    }

    /**
     * Create a pending upload session.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadSession::STATUS_PENDING,
            'file_id' => null,
        ]);
    }

    /**
     * Create a complete upload session with an associated file.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadSession::STATUS_COMPLETE,
            'file_id' => File::factory(),
        ]);
    }

    /**
     * Create a failed upload session.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadSession::STATUS_FAILED,
            'file_id' => null,
        ]);
    }

    /**
     * Create a stale upload session (pending for more than 24 hours).
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UploadSession::STATUS_PENDING,
            'file_id' => null,
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ]);
    }

    /**
     * Create an image upload session.
     */
    public function image(): static
    {
        $name = fake()->word() . '.jpg';

        return $this->state(fn (array $attributes) => [
            'filename' => $name,
            'filetype' => 'image/jpeg',
        ]);
    }

    /**
     * Create a large file upload session.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'filesize' => fake()->numberBetween(500000000, 2000000000), // 500MB - 2GB
        ]);
    }
}

