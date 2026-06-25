<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignUuid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->uuid('receptionist_id')->nullable(); // user who created the booking

            $table->string('booking_reference')->unique(); // AFS-{HOTEL}-{YYYYMMDD}-{RANDOM4}
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('nights'); // computed at save time, not a DB-generated column (portability)

            $table->bigInteger('total_amount')->default(0); // kobo — room charge + extras
            $table->bigInteger('amount_paid')->default(0);  // kobo
            $table->bigInteger('balance')->default(0);      // kobo

            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('pending');
            $table->enum('booking_source', ['walk_in', 'online', 'phone'])->default('walk_in');
            $table->text('notes')->nullable();

            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
            $table->index(['room_id', 'check_in', 'check_out']); // for the double-booking overlap query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
