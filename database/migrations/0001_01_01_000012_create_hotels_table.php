<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Nigeria');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('logo')->nullable(); // Cloudinary URL
            $table->text('description')->nullable();
            $table->enum('tier', ['starter', 'growth', 'pro', 'enterprise'])->default('starter');
            $table->bigInteger('wallet_balance')->default(0); // kobo

            // owner_id intentionally NOT a foreign-key constraint here — the
            // users table (which depends on hotels.id) is created in the next
            // migration, so this just stores the uuid to avoid a circular dependency.
            $table->uuid('owner_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->boolean('onboarding_completed')->default(false);

            // Subscription/billing gate — see subscriptions table for the
            // authoritative record; these are denormalised for fast middleware checks.
            $table->enum('subscription_status', ['pending_payment', 'active', 'past_due', 'expired', 'cancelled'])
                ->default('pending_payment');
            $table->timestamp('subscription_ends_at')->nullable();

            $table->timestamps();

            $table->index('owner_id');
            $table->index('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
