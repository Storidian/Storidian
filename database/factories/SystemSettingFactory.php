<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->boolean(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a registration enabled setting.
     */
    public function registrationEnabled(bool $enabled = true): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'registration_enabled',
            'value' => $enabled,
        ]);
    }

    /**
     * Create a storage disk setting.
     */
    public function storageDisk(string $disk = 'local'): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'storage_disk',
            'value' => $disk,
        ]);
    }
}

