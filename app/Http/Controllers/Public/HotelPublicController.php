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

        $roomTypes = $hotel->rooms()
            ->where('status', '!=', 'maintenance')
            ->with('media')
            ->get()
            ->groupBy('type');

        return view('public.hotel-page', [
            'hotel' => $hotel,
            'roomTypes' => $roomTypes,
        ]);
    }

    /** AJAX: given a room type + dates, which specific rooms are free. Same overlap rule as the staff-side booking flow. */
    public function checkAvailability(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', 'in:standard,deluxe,suite,family'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1'],
        ]);

        $rooms = $hotel->rooms()
            ->where('type', $validated['type'])
            ->where('status', 'available')
            ->when(! empty($validated['guests']), fn ($q) => $q->where('max_guests', '>=', $validated['guests']))
            ->with('media')
            ->get()
            ->reject(fn (Room $room) => Booking::hasOverlap($room->id, $validated['check_in'], $validated['check_out']))
            ->values();

        return response()->json($rooms->map(fn ($r) => [
            'id' => $r->id,
            'room_number' => $r->room_number,
            'name' => $r->name,
            'price_per_night_naira' => $r->pricePerNightNaira(),
            'max_guests' => $r->max_guests,
            'image' => $r->media->firstWhere('type', 'image')?->url,
        ]));
    }

    /**
     * Creates the booking (status: pending — this itself reserves the room
     * via the same hasOverlap() check everywhere else uses) and redirects
     * the guest to a deposit checkout. The booking only becomes `confirmed`
     * once GuestPaymentConfirmationService processes the webhook.
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

        // At least one contact method is required for online bookings so the
        // confirmation can actually be delivered — unlike walk-ins, there's
        // no receptionist standing there to relay it verbally.
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
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $total = $nights * $room->price_per_night;

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
                'nights' => $nights,
                'total_amount' => $total,
                'amount_paid' => 0,
                'balance' => $total,
                'status' => 'pending', // confirmed only once the deposit clears
                'booking_source' => 'online',
                'notes' => $validated['special_requests'] ?? null,
            ]);

            ActivityLog::record($hotel->id, null, 'CREATE_ONLINE_BOOKING', 'booking', 'Booking', $booking->id, $booking->booking_reference,
                "Guest {$guest->name} started an online booking — Room {$room->room_number}, {$nights} night(s). Awaiting deposit.");

            return $booking;
        });

        $depositPercent = $hotel->online_booking_deposit_percent;
        $depositAmount = (int) round($booking->total_amount * ($depositPercent / 100));

        try {
            $result = $this->payments->initiateDepositCheckout($booking, $depositAmount);
        } catch (\Throwable $e) {
            return back()->withErrors(['room_id' => $e->getMessage()])->withInput();
        }

        return redirect()->away($result['checkout_url']);
    }

    /** Where the provider redirects back to after payment — the webhook is the source of truth, this is just UX. */
    public function callback(Request $request, string $slug)
    {
        $hotel = Hotel::where('slug', $slug)->firstOrFail();
        $reference = $request->query('tx_ref') ?? $request->query('reference');

        $booking = $reference
            ? \App\Models\Payment::where('payment_reference', $reference)->first()?->booking
            : null;

        if ($booking && $booking->fresh()->status === 'confirmed') {
            return redirect()->route('public.booking.confirmation', [$slug, $booking->booking_reference]);
        }

        return redirect()->route('public.hotel.show', $slug)
            ->with('info', "We're still confirming your payment — this can take a minute. If it doesn't update shortly, contact the hotel directly with reference {$booking?->booking_reference}.");
    }

    public function confirmation(string $slug, string $reference)
    {
        $hotel = Hotel::where('slug', $slug)->firstOrFail();
        $booking = $hotel->bookings()->with(['room', 'guest'])->where('booking_reference', $reference)->firstOrFail();

        return view('public.booking-confirmation', ['hotel' => $hotel, 'booking' => $booking]);
    }
}
