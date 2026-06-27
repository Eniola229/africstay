<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Was date-only — now carries time so hourly/24h-block rooms work.
            $table->dateTime('check_in')->change();
            $table->dateTime('check_out')->change();

            // Snapshot of the room's pricing unit AT BOOKING TIME, so a later
            // change to the room's pricing unit never rewrites history.
            $table->enum('pricing_unit', ['night', 'hour', 'day24'])->default('night')->after('nights');

            // Used by the due-checkout scheduler to avoid re-alerting the same booking.
            $table->boolean('checkout_alert_sent')->default(false)->after('checked_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('check_in')->change();
            $table->date('check_out')->change();
            $table->dropColumn(['pricing_unit', 'checkout_alert_sent']);
        });
    }
};