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
        Schema::create('system_api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete(); // Admin who created
            $table->string('name'); // Integration name
            $table->string('key_hash'); // Hashed API key
            $table->string('key_prefix', 16); // First 8 chars (e.g., strd_s_xyz)
            $table->json('scopes'); // Permission scopes
            $table->json('allowed_users')->nullable(); // Limit to specific users (null = all)
            $table->timestamp('expires_at')->nullable(); // Optional expiration
            $table->timestamp('last_used_at')->nullable(); // Last API call
            $table->timestamps();

            $table->index('key_prefix'); // Quick lookup by prefix
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_api_keys');
    }
};

