<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->enum('tier', ['starter', 'growth', 'pro', 'enterprise']);
            $table->bigInteger('monthly_fee');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};