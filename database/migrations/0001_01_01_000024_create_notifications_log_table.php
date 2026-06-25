<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->uuid('guest_id')->nullable();
            $table->enum('type', ['sms', 'email']);
            $table->string('recipient'); // phone or email actually used
            $table->text('message');
            $table->string('provider_reference')->nullable();
            $table->enum('status', ['sent', 'failed', 'skipped'])->default('sent');
            $table->boolean('was_fallback')->default(false); // true if this went to the HOTEL's contact, not the guest's
            $table->string('event')->nullable(); // e.g. booking_confirmed, check_in, payment_received, check_out
            $table->timestamp('created_at')->nullable();

            $table->index(['hotel_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};
