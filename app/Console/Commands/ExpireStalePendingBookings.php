<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * An online booking reserves its room the moment it's created (status:
 * pending counts as "active" in Booking::hasOverlap()) — but if the guest
 * abandons the checkout page, that room would stay blocked forever. This
 * sweeps pending ONLINE bookings older than the threshold and cancels them,
 * which removes them from the overlap check and frees the room.
 *
 * Walk-in bookings are never touched here — they're created as `confirmed`
 * immediately, not `pending`.
 */
class ExpireStalePendingBookings extends Command
{
    protected $signature = 'bookings:expire-stale-pending {--minutes=120}';
    protected $description = 'Cancel abandoned online bookings (pending, unpaid) older than N minutes to free up the room.';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $stale = Booking::where('status', 'pending')
            ->where('booking_source', 'online')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->get();

        foreach ($stale as $booking) {
            $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $this->info("Cancelled stale pending booking {$booking->booking_reference}.");
        }

        $this->info("Swept {$stale->count()} stale pending booking(s).");

        return self::SUCCESS;
    }
}
