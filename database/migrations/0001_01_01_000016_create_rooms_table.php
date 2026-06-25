<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('room_number');
            $table->string('name')->nullable();
            $table->enum('type', ['standard', 'deluxe', 'suite', 'family'])->default('standard');
            $table->string('floor')->nullable();
            $table->bigInteger('price_per_night'); // kobo
            $table->enum('status', ['available', 'occupied', 'dirty', 'maintenance'])->default('available');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_guests')->default(2);
            $table->string('maintenance_reason')->nullable();
            $table->date('maintenance_expected_return')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['hotel_id', 'room_number']);
            $table->index(['hotel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
