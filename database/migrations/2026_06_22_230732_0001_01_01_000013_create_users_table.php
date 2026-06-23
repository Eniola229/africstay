<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->enum('role', [
                'owner', 'manager', 'receptionist', 'cashier',
                'housekeeper', 'room_service', 'accountant',
            ])->default('receptionist');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('invite_token')->nullable();
            $table->timestamp('invite_expires_at')->nullable();
            $table->boolean('must_set_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();

            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_otps');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};