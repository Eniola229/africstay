<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->boolean('online_booking_enabled')->default(true);
            // Minimum deposit guests must pay online, per the spec ("minimum 50% - configurable by hotel").
            $table->unsignedTinyInteger('online_booking_deposit_percent')->default(50);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['online_booking_enabled', 'online_booking_deposit_percent', 'meta_title', 'meta_description']);
        });
    }
};
