<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // night = price_per_night charged per calendar night
            // hour  = price_per_night charged per hour
            // day24 = price_per_night charged per rolling 24-hour block
            $table->enum('pricing_unit', ['night', 'hour', 'day24'])->default('night')->after('price_per_night');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('pricing_unit');
        });
    }
};