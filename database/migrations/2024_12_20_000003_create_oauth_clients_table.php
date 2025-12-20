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
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Application name
            $table->string('client_id')->unique(); // Public client identifier
            $table->string('client_secret')->nullable(); // Secret for confidential clients
            $table->json('redirect_uris'); // Allowed redirect URIs
            $table->json('scopes'); // Allowed scopes for this client
            $table->boolean('is_first_party')->default(false); // Skip consent screen
            $table->boolean('is_public')->default(false); // Public client (no secret, PKCE required)
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};

