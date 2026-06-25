<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->enum('tier', ['starter', 'growth', 'pro', 'enterprise']);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');

            $table->bigInteger('base_monthly_fee'); // kobo, before discount
            $table->unsignedTinyInteger('discount_percent')->default(0); // 20 for yearly
            $table->bigInteger('amount_due'); // kobo, what was actually charged for this period

            // pending: created but not yet paid; active: paid & current;
            // past_due: payment failed/expired but within grace; expired/cancelled: no access.
            $table->enum('status', ['pending', 'active', 'past_due', 'expired', 'cancelled'])->default('pending');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // when this paid period runs out
            $table->boolean('renewal_reminder_7d_sent')->default(false);
            $table->boolean('renewal_reminder_3d_sent')->default(false);
            $table->boolean('renewal_reminder_1d_sent')->default(false);
            $table->boolean('is_active')->default(false); // current/latest subscription record for the hotel
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'is_active']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
