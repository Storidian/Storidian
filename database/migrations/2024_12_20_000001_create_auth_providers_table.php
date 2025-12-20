<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (e.g., "Google", "Company SSO")
            $table->string('slug')->unique(); // URL-safe identifier
            $table->string('provider_class'); // Socialite driver or OIDC
            $table->json('config'); // Provider-specific configuration
            $table->boolean('enabled')->default(false);
            $table->boolean('allow_registration')->default(false); // Create users on first SSO login
            $table->boolean('trust_email')->default(false); // Auto-link accounts with matching email
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_providers');
    }
};

