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
        Schema::create('user_api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); // User-provided name
            $table->string('key_hash'); // Hashed API key
            $table->string('key_prefix', 16); // First 8 chars (e.g., strd_u_abc)
            $table->json('scopes'); // Permission scopes
            $table->foreignUuid('folder_scope')->nullable()->constrained('folders')->nullOnDelete(); // Limit to folder subtree
            $table->timestamp('expires_at')->nullable(); // Optional expiration
            $table->timestamp('last_used_at')->nullable(); // Last API call
            $table->timestamps();

            $table->index(['user_id', 'created_at']); // List user's keys
            $table->index('key_prefix'); // Quick lookup by prefix
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_api_keys');
    }
};

