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
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('name'); // Display name
            $table->string('original_name'); // Name at upload time
            $table->string('mime_type');
            $table->bigInteger('size'); // Size in bytes
            $table->string('storage_path'); // Path in storage backend
            $table->string('storage_disk'); // Flysystem disk identifier
            $table->string('checksum', 64); // SHA-256 hash
            $table->json('metadata')->nullable(); // Additional metadata (EXIF, etc.)
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'folder_id']); // Fast file listing
            $table->index(['user_id', 'deleted_at']); // Trash queries
            $table->index('mime_type'); // Filter by type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};

