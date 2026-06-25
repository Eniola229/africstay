<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->bigInteger('amount'); // kobo
            $table->enum('payment_method', ['virtual_account', 'card', 'cash', 'transfer'])->default('virtual_account');
            $table->enum('provider', ['flutterwave', 'paystack', 'cash'])->default('flutterwave');

            $table->string('virtual_account_number')->nullable();
            $table->string('virtual_account_bank')->nullable();
            $table->string('virtual_account_name')->nullable();

            $table->string('payment_reference')->unique(); // our own ref — webhook idempotency key
            $table->string('provider_reference')->nullable();

            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
