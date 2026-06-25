<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per attempted subscription payment (initial signup, renewal, upgrade).
        // payment_reference is unique so webhooks are idempotent — never double-activate.
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->enum('tier', ['starter', 'growth', 'pro', 'enterprise']);
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->bigInteger('amount'); // kobo, actually charged
            $table->enum('provider', ['flutterwave', 'paystack']);
            $table->string('payment_reference')->unique(); // our own ref, e.g. AFS-SUB-...
            $table->string('provider_reference')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
