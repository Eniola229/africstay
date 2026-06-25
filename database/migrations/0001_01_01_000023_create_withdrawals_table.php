<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->bigInteger('amount'); // kobo
            $table->string('bank_name');
            $table->string('bank_code')->nullable(); // needed by Flutterwave/Paystack transfer APIs
            $table->string('account_number');
            $table->string('account_name');
            $table->string('reference')->unique();
            $table->enum('provider', ['flutterwave', 'paystack'])->default('flutterwave');
            $table->string('provider_reference')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->uuid('initiated_by')->nullable(); // user id (must be owner — enforced in controller)
            $table->string('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
