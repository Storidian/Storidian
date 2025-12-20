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
        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('upload_id')->unique(); // tusd-generated ID (32-char hex)
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('filename'); // Original filename from metadata
            $table->bigInteger('filesize'); // Expected file size in bytes
            $table->string('filetype'); // MIME type
            $table->string('status')->default('pending'); // pending, complete, failed
            $table->foreignUuid('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']); // Find user's pending uploads
            $table->index(['status', 'created_at']); // Cleanup stale uploads
            $table->index('upload_id'); // Lookup by tusd ID (already unique, but explicit index)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_sessions');
    }
};

