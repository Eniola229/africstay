<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Runs every minute (scheduled in routes/console.php or bootstrap/app.php).
 *
 * Finds checked-in bookings whose check_out is within the next 60 minutes
 * and broadcasts a real-time alert to the hotel's front-desk staff via an
 * echo event so a push notification / popup appears in the dashboard.
 *
 * Also sends an SMS to the manager as a fallback.
 *
 * Marks checkout_alert_sent = true so each booking is only alerted once.
 */
class CheckoutDueAlertCommand extends Command
{
    protected $signature   = 'africstay:checkout-due-alerts';
    protected $description = 'Send checkout-due alerts for bookings expiring within the next 60 minutes.';

    public function __construct(protected SmsService $sms)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now       = Carbon::now();
        $threshold = $now->copy()->addMinutes(60);

        $due = Booking::with(['room', 'guest', 'hotel.users'])
            ->where('status', 'checked_in')
            ->where('checkout_alert_sent', false)
            ->where('check_out', '<=', $threshold) 
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        // Group by hotel so we can send one batched event per hotel
        $byHotel = $due->groupBy('hotel_id');

        foreach ($byHotel as $hotelId => $bookings) {
            $hotel = $bookings->first()->hotel;

            $roomSummaries = $bookings->map(function ($b) {
                $minutesLeft = (int) Carbon::now()->diffInMinutes($b->check_out, false);
                $minutesLeft = max(0, $minutesLeft);
                return [
                    'booking_reference' => $b->booking_reference,
                    'room_number'       => $b->room->room_number,
                    'guest_name'        => $b->guest->name,
                    'check_out'         => $b->check_out->format('H:i'),
                    'minutes_left'      => $minutesLeft,
                    'balance_naira'     => $b->balanceNaira(),
                ];
            })->values()->toArray();

            // ── Broadcast to front-end (Laravel Reverb / Pusher) ─────────────
            // Fires the CheckoutDueAlert event which the Blade JS listens for.
            try {
                event(new \App\Events\CheckoutDueAlert($hotelId, $roomSummaries));
            } catch (\Throwable $e) {
                Log::warning("CheckoutDueAlert broadcast failed for hotel {$hotelId}: ".$e->getMessage());
            }

            // ── SMS fallback to manager ───────────────────────────────────────
            $manager = $hotel->users()->whereIn('role', ['manager', 'owner'])->whereNotNull('phone')->first();
            if ($manager) {
                $names = collect($roomSummaries)->pluck('room_number')->join(', ');
                $this->sms->send(
                    $manager->phone,
                    count($roomSummaries) === 1
                        ? "AfricStay: Room {$names} checkout due in ~{$roomSummaries[0]['minutes_left']} min. Guest: {$roomSummaries[0]['guest_name']}."
                        : "AfricStay: ".count($roomSummaries)." rooms due for checkout soon: {$names}."
                );
            }

            // Mark all as alerted
            Booking::whereIn('id', $bookings->pluck('id'))->update(['checkout_alert_sent' => true]);

            $this->info("Alerted ".count($roomSummaries)." due checkout(s) for hotel {$hotel->name}.");
        }

        return self::SUCCESS;
    }
}