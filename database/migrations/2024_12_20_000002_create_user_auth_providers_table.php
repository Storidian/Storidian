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
        Schema::create('user_auth_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('auth_providers')->cascadeOnDelete();
            $table->string('provider_user_id'); // External provider's user ID
            $table->json('provider_data')->nullable(); // Additional data from provider
            $table->timestamps();

            $table->unique(['provider_id', 'provider_user_id']); // One user per provider account
            $table->unique(['user_id', 'provider_id']); // One provider account per user per provider
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_auth_providers');
    }
};

