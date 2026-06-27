<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Room;
use App\Services\NotificationFallbackService;
use App\Services\OnlineBookingPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The public, unauthenticated booking page at /hotel/{slug} — see spec
 * section "Online Booking Page". No guard, no login required; this is what
 * a guest sees when a hotel shares their AfricStay link.
 */
class HotelPublicController extends Controller
{
    public function __construct(
        protected NotificationFallbackService $notify,
        protected OnlineBookingPaymentService $payments,
    ) {}

    public function show(string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('is_active', true)->firstOrFail();
     
        if (! $hotel->online_booking_enabled) {
            abort(404);
        }
     
        // Group rooms by type; each group carries the media relation for the showcase
        $roomTypes = $hotel->rooms()
            ->where('status', '!=', 'maintenance')
            ->with('media')
            ->orderBy('price_per_night')
            ->get()
            ->groupBy('type');
     
        return view('public.hotel-page', [
            'hotel'     => $hotel,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function checkAvailability(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('is_active', true)->firstOrFail();
     
        $validated = $request->validate([
            'type'      => ['nullable', 'in:standard,deluxe,suite,family'],
            'check_in'  => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests'    => ['nullable', 'integer', 'min:1'],
        ]);
     
        $rooms = $hotel->rooms()
            ->where('status', 'available')
            ->when(! empty($validated['type']), fn ($q) => $q->where('type', $validated['type']))
            ->when(! empty($validated['guests']), fn ($q) => $q->where('max_guests', '>=', $validated['guests']))
            ->with('media')
            ->get()
            ->reject(fn (Room $room) => Booking::hasOverlap(
                $room->id,
                $validated['check_in'],
                $validated['check_out']
            ))
            ->values();
     
        return response()->json($rooms->map(fn ($r) => [
            'id'                    => $r->id,
            'room_number'           => $r->room_number,
            'name'                  => $r->name,
            'type'                  => $r->type,
            'pricing_unit'          => $r->pricing_unit ?? 'night',
            'price_per_night_naira' => $r->pricePerNightNaira(),
            'max_guests'            => $r->max_guests,
            'image'                 => $r->media->firstWhere('type', 'image')?->url,
        ]));
    }

    /**
     * Creates the booking (status: pending) and redirects to payment
     * Now charges the FULL amount instead of just deposit
     */
    public function store(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'room_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guest_name' => ['required', 'string', 'max:150'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'guest_email' => ['nullable', 'email', 'max:150'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        if (blank($validated['guest_phone']) && blank($validated['guest_email'])) {
            return back()->withErrors(['guest_phone' => 'Please provide at least a phone number or email so we can send your booking confirmation.'])->withInput();
        }

        $room = $hotel->rooms()->where('status', 'available')->findOrFail($validated['room_id']);

        if (Booking::hasOverlap($room->id, $validated['check_in'], $validated['check_out'])) {
            return back()->withErrors(['room_id' => 'Sorry, that room was just booked for those dates. Please pick another.'])->withInput();
        }

        $booking = DB::transaction(function () use ($validated, $hotel, $room) {
            $guest = Guest::create([
                'hotel_id' => $hotel->id,
                'name' => $validated['guest_name'],
                'phone' => $validated['guest_phone'] ?? null,
                'email' => $validated['guest_email'] ?? null,
            ]);

            $checkIn = \Carbon\Carbon::parse($validated['check_in']);
            $checkOut = \Carbon\Carbon::parse($validated['check_out']);
            
            $pricingUnit = $room->pricing_unit ?? 'night';
            $pricePerUnit = $room->price_per_night;
            
            switch ($pricingUnit) {
                case 'hour':
                    $units = max(1, (int) ceil($checkIn->diffInHours($checkOut)));
                    break;
                case 'day24':
                    $hours = $checkIn->diffInMinutes($checkOut) / 60;
                    $units = max(1, (int) ceil($hours / 24));
                    break;
                case 'night':
                default:
                    $units = max(1, $checkIn->diffInDays($checkOut));
                    break;
            }
            
            $total = $units * $pricePerUnit;

            $shortCode = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $hotel->name), 0, 3)) ?: 'AFS';
            $reference = "AFS-{$shortCode}-".now()->format('Ymd').'-'.Str::upper(Str::random(4));

            $booking = Booking::create([
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'receptionist_id' => null,
                'booking_reference' => $reference,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $units,
                'pricing_unit' => $pricingUnit,
                'total_amount' => $total,
                'amount_paid' => 0,
                'balance' => $total,
                'status' => 'pending',
                'booking_source' => 'online',
                'notes' => $validated['special_requests'] ?? null,
            ]);

            ActivityLog::record($hotel->id, null, 'CREATE_ONLINE_BOOKING', 'booking', 'Booking', $booking->id, $booking->booking_reference,
                "Guest {$guest->name} started an online booking — Room {$room->room_number}, {$units} {$pricingUnit}(s). Awaiting full payment.");

            return $booking;
        });

        // 🔥 PAY FULL AMOUNT - NOT JUST DEPOSIT
        $amountToCharge = $booking->total_amount; // Full amount in kobo

        try {
            $result = $this->payments->initiateFullPaymentCheckout($booking, $amountToCharge);
            
            session(['pending_booking_id' => $booking->id]);
            session(['payment_reference' => $result['payment_reference'] ?? null]);
            
        } catch (\Throwable $e) {
            $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            return back()->withErrors(['room_id' => 'Payment could not be initiated. Please try again.'])->withInput();
        }

        return redirect()->away($result['checkout_url']);
    }

    /**
     * Where the provider redirects back to after payment — the webhook is the source of truth, this is just UX.
     */
public function callback(Request $request, string $slug)
{
    $hotel = Hotel::where('slug', $slug)->firstOrFail();
    
    // Flutterwave sends tx_ref in the query string
    $reference = $request->query('tx_ref') ?? $request->query('reference');
    $status = $request->query('status') ?? $request->query('state');
    
    // Log what we received
    \Log::info('Flutterwave callback received', [
        'reference' => $reference,
        'status' => $status,
        'all_params' => $request->all()
    ]);
    
    // Find the payment by reference - this should work
    $payment = $reference ? \App\Models\Payment::where('payment_reference', $reference)->first() : null;
    $booking = $payment?->booking;
    
    // If we found the booking and it's confirmed or checked_in, go to confirmation
    if ($booking && ($booking->status === 'confirmed' || $booking->status === 'checked_in')) {
        return redirect()->route('public.booking.confirmation', [$slug, $booking->booking_reference]);
    }
    
    // If booking is still pending, go to confirmation page with pending status
    if ($booking && $booking->status === 'pending') {
        return redirect()->route('public.booking.confirmation', [$slug, $booking->booking_reference])
            ->with('pending', 'Your payment is being processed. This page will auto-refresh to check the status.');
    }
    
    // If we found the booking but status is something else
    if ($booking) {
        return redirect()->route('public.hotel.show', $slug)
            ->with('info', 'Your booking status: ' . $booking->status . '. Please contact the hotel if you have questions.');
    }
    
    // Fallback - no booking found
    return redirect()->route('public.hotel.show', $slug)
        ->with('info', 'We could not find your booking. Please contact the hotel directly.');
}

    /**
     * Helper method to cancel a booking and free up the room
     */
    protected function cancelBooking($booking, $hotel, $reason = 'Payment was not completed.')
    {
        DB::transaction(function () use ($booking, $hotel, $reason) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
            
            // Make room available again
            if ($booking->room) {
                $booking->room->update(['status' => 'available']);
            }
            
            ActivityLog::record($hotel->id, null, 'CANCEL_ONLINE_BOOKING', 'booking', 'Booking', $booking->id, $booking->booking_reference,
                "Online booking {$booking->booking_reference} was cancelled. Reason: {$reason}");
        });
    }
    public function confirmation(string $slug, string $reference)
    {
        $hotel = Hotel::where('slug', $slug)->firstOrFail();
        $booking = $hotel->bookings()->with(['room', 'guest'])->where('booking_reference', $reference)->firstOrFail();

        return view('public.booking-confirmation', ['hotel' => $hotel, 'booking' => $booking]);
    }

    /**
     * Check payment status via AJAX for the confirmation page
     */
    public function checkPaymentStatus(string $slug, string $reference)
    {
        $hotel = Hotel::where('slug', $slug)->firstOrFail();
        $booking = $hotel->bookings()->with(['room', 'guest', 'payments'])->where('booking_reference', $reference)->firstOrFail();
        
        $latestPayment = $booking->payments->sortByDesc('created_at')->first();
        
        // Check if booking was auto-cancelled due to timeout
        if ($booking->status === 'cancelled') {
            return response()->json([
                'status' => $booking->status,
                'payment_confirmed' => false,
                'payment_status' => 'cancelled',
                'balance' => $booking->balance,
                'amount_paid' => $booking->amount_paid,
                'booking_reference' => $booking->booking_reference,
                'message' => 'Booking was cancelled because payment was not completed.',
            ]);
        }
        
        return response()->json([
            'status' => $booking->status,
            'payment_confirmed' => $booking->status === 'confirmed' || $booking->status === 'checked_in',
            'payment_status' => $latestPayment?->status ?? 'no_payment',
            'balance' => $booking->balance,
            'amount_paid' => $booking->amount_paid,
            'booking_reference' => $booking->booking_reference,
        ]);
    }
}