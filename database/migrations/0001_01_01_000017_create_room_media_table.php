<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photos AND short video clips for a room, stored on Cloudinary.
        // cloudinary_public_id is kept so we can delete the asset from Cloudinary
        // (not just the DB row) when a media item is removed.
        Schema::create('room_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('url'); // Cloudinary secure_url
            $table->string('cloudinary_public_id')->nullable();
            $table->string('thumbnail_url')->nullable(); // for videos, a poster frame
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['room_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_media');
    }
};
