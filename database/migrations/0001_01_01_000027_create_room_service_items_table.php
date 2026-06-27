<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_service_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name');
            $table->enum('category', ['food', 'drink', 'laundry', 'misc'])->default('misc');
            $table->bigInteger('price'); // kobo
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['hotel_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_service_items');
    }
};
