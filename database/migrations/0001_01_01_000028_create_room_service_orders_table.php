<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('room_service_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_price'); // kobo, snapshot at order time
            $table->bigInteger('total_price'); // kobo
            $table->enum('status', ['pending', 'in_progress', 'delivered', 'cancelled'])->default('pending');
            $table->uuid('requested_by')->nullable(); // user_id
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_service_orders');
    }
};
