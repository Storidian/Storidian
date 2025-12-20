<?php

namespace Database\Seeders;

use App\Models\OauthClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OAuthClientSeeder extends Seeder
{
    /**
     * Seed the first-party OAuth clients.
     *
     * These clients are pre-registered for official Storidian apps.
     * First-party clients skip the consent screen.
     */
    public function run(): void
    {
        // Storidian Web UI
        OauthClient::updateOrCreate(
            ['client_id' => 'storidian-web'],
            [
                'name' => 'Storidian Web',
                'client_secret' => null, // Public client
                'redirect_uris' => [
                    config('app.url').'/callback',
                    config('app.url').'/auth/callback',
                    'http://localhost:5173/callback', // Vite dev server
                    'http://localhost:5173/auth/callback',
                    'http://localhost:8000/callback',
                    'http://localhost:8000/auth/callback',
                ],
                'scopes' => ['*'], // Full access
                'is_first_party' => true,
                'is_public' => true,
                'created_by' => null, // System
            ]
        );

        // Storidian iOS App
        OauthClient::updateOrCreate(
            ['client_id' => 'storidian-ios'],
            [
                'name' => 'Storidian iOS',
                'client_secret' => null, // Public client
                'redirect_uris' => [
                    'storidian://callback',
                    'storidian://auth/callback',
                ],
                'scopes' => ['*'], // Full access
                'is_first_party' => true,
                'is_public' => true,
                'created_by' => null,
            ]
        );

        // Storidian Android App
        OauthClient::updateOrCreate(
            ['client_id' => 'storidian-android'],
            [
                'name' => 'Storidian Android',
                'client_secret' => null, // Public client
                'redirect_uris' => [
                    'storidian://callback',
                    'storidian://auth/callback',
                ],
                'scopes' => ['*'], // Full access
                'is_first_party' => true,
                'is_public' => true,
                'created_by' => null,
            ]
        );

        $this->command->info('First-party OAuth clients seeded successfully.');
    }
}

