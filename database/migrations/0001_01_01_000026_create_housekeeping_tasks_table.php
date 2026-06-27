<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->uuid('assigned_to')->nullable(); // user_id of housekeeper
            $table->enum('triggered_by', ['checkout', 'manual'])->default('checkout');
            $table->enum('status', ['pending', 'in_progress', 'cleaned', 'verified'])->default('pending');
            $table->json('checklist')->nullable(); // [{label, done}, ...] — configurable per room type
            $table->timestamp('completed_at')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_tasks');
    }
};
