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
        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable(); // Self-reference (null = root)
            $table->string('name');
            $table->string('color')->nullable(); // UI color hint
            $table->string('icon')->nullable(); // UI icon hint
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('folders')->cascadeOnDelete();
            $table->index(['user_id', 'parent_id']); // Fast folder listing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};

