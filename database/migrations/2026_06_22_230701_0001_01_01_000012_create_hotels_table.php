<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Nigeria');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->enum('tier', ['starter', 'growth', 'pro', 'enterprise'])->default('starter');
            $table->bigInteger('wallet_balance')->default(0);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamps();

            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};