<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            // Multi-location (Pro tier, up to 3 locations per spec). A child
            // location points back at the "primary" hotel record the owner
            // actually logs into. NULL = this IS a primary/standalone hotel.
            $table->uuid('parent_hotel_id')->nullable();
            $table->string('brand_primary_color')->nullable(); // Pro: branded booking page
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['parent_hotel_id', 'brand_primary_color']);
        });
    }
};
