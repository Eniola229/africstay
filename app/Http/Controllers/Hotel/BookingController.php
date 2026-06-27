<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Services\NotificationFallbackService;
use App\Services\SmsService;
use App\Services\VirtualAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function __construct(
        protected NotificationFallbackService $notify,
        protected VirtualAccountService $virtualAccounts,
        protected SmsService $sms,
    ) {}

    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    protected function authorizeBookingStaff(): void
    {
        if (! in_array(Auth::user()->role, ['owner', 'manager', 'receptionist'])) {
            abort(403, 'Only owners, managers and receptionists can manage bookings.');
        }
    }

    public function index(Request $request)
    {
        $query = $this->hotel()->bookings()->with(['room', 'guest'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_reference', 'like', "%{$search}%")
                  ->orWhereHas('guest', fn ($g) => $g->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        return view('hotel.bookings.index', [
            'bookings' => $query->paginate(20)->withQueryString(),
            'currentStatus' => $request->query('status', 'all'),
        ]);
    }

    public function create()
    {
        $this->authorizeBookingStaff();
        return view('hotel.bookings.create', [
            'roomTypes' => ['standard', 'deluxe', 'suite', 'family'],
        ]);
    }

    /** Step inside the new-booking form: which rooms of a given type are free for these dates. */
    public function availableRooms(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:standard,deluxe,suite,family'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        $rooms = $this->hotel()->rooms()
            ->where('type', $validated['type'])
            ->where('status', 'available')
            ->get()
            ->reject(fn (Room $room) => Booking::hasOverlap($room->id, $validated['check_in'], $validated['check_out']))
            ->values();

        return response()->json($rooms->map(fn ($r) => [
            'id' => $r->id,
            'room_number' => $r->room_number,
            'price_per_night' => $r->price_per_night,
            'price_per_night_naira' => $r->pricePerNightNaira(),
        ]));
    }

    public function store(Request $request)
    {
        $this->authorizeBookingStaff();
        $hotel = $this->hotel();

        $validated = $request->validate([
            'guest_id' => ['nullable', 'uuid'],
            'guest_name' => ['required_without:guest_id', 'string', 'max:150'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'guest_email' => ['nullable', 'email', 'max:150'],
            'room_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $room = $hotel->rooms()->findOrFail($validated['room_id']);

        if ($room->status !== 'available') {
            return back()->withErrors(['room_id' => "Room {$room->room_number} is not currently available."])->withInput();
        }

        // The double-booking guard — checked again here server-side even though
        // availableRooms() already filtered, since dates/room could be stale by submit time.
        if (Booking::hasOverlap($room->id, $validated['check_in'], $validated['check_out'])) {
            return back()->withErrors(['room_id' => "Room {$room->room_number} is already booked for those dates."])->withInput();
        }

        $booking = DB::transaction(function () use ($validated, $hotel, $room) {
            if (! empty($validated['guest_id'])) {
                $guest = $hotel->guests()->findOrFail($validated['guest_id']);
            } else {
                $guest = Guest::create([
                    'hotel_id' => $hotel->id,
                    'name' => $validated['guest_name'],
                    'phone' => $validated['guest_phone'] ?? null,
                    'email' => $validated['guest_email'] ?? null,
                ]);
            }

            $checkIn = \Carbon\Carbon::parse($validated['check_in']);
            $checkOut = \Carbon\Carbon::parse($validated['check_out']);
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $total = $nights * $room->price_per_night;

            $reference = $this->generateReference($hotel);

            $booking = Booking::create([
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'receptionist_id' => Auth::id(),
                'booking_reference' => $reference,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'total_amount' => $total,
                'amount_paid' => 0,
                'balance' => $total,
                'status' => 'confirmed',
                'booking_source' => 'walk_in',
                'notes' => $validated['notes'] ?? null,
            ]);

            ActivityLog::record($hotel->id, Auth::user(), 'CREATE_BOOKING', 'booking', 'Booking', $booking->id,
                $booking->booking_reference,
                Auth::user()->name." created booking {$booking->booking_reference} for {$guest->name} — Room {$room->room_number}, {$nights} night(s).");

            return $booking;
        });

        $booking->load('guest', 'room');

        $this->notify->notify(
            $hotel,
            $booking->guest,
            'booking_confirmed',
            "Dear {$booking->guest->name}, your booking at {$hotel->name} for Room {$booking->room->room_number} on {$booking->check_in->format('d M Y')} is confirmed. Ref: {$booking->booking_reference}.",
            'Your AfricStay booking is confirmed',
            "<p>Your booking at {$hotel->name} is confirmed. Reference: <strong>{$booking->booking_reference}</strong>.</p>"
        );

        return redirect()->route('hotel.bookings.show', $booking->id)->with('success', "Booking {$booking->booking_reference} created.");
    }

    public function show(string $booking)
    {
        $booking = $this->hotel()->bookings()->with(['room.media', 'guest', 'payments', 'roomServiceOrders.item'])->findOrFail($booking);

        return view('hotel.bookings.show', ['booking' => $booking]);
    }

    /** Check-in: confirms identity, flips room to occupied, generates the guest's virtual account. */
    public function checkIn(Request $request, string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel = $this->hotel();
        $booking = $hotel->bookings()->with(['room', 'guest'])->findOrFail($booking);

        if ($booking->status !== 'confirmed') {
            return back()->withErrors(['booking' => 'Only confirmed bookings can be checked in.']);
        }

        $validated = $request->validate([
            'id_type' => ['required', 'in:nin,passport,drivers_license,other'],
            'id_number' => ['required', 'string', 'max:100'],
        ]);

        $booking->guest->update(['id_type' => $validated['id_type'], 'id_number' => $validated['id_number']]);

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'checked_in', 'checked_in_at' => now()]);
            $booking->room->update(['status' => 'occupied']);
        });

        $payment = $this->virtualAccounts->generate($booking);

        ActivityLog::record($hotel->id, Auth::user(), 'CHECK_IN', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." checked in guest {$booking->guest->name} to Room {$booking->room->room_number}. Booking ref: {$booking->booking_reference}.");

        $accountMsg = "Payment account for your stay: {$payment->virtual_account_number} ({$payment->virtual_account_bank}), amount due ₦".number_format($payment->amountNaira(), 2).". Ref: {$booking->booking_reference}.";

        $this->notify->notify(
            $hotel,
            $booking->guest,
            'check_in',
            $accountMsg,
            'Your payment details — AfricStay',
            "<p>{$accountMsg}</p>"
        );

        return redirect()->route('hotel.bookings.show', $booking->id)
            ->with('success', "Checked in. Payment account: {$payment->virtual_account_number} ({$payment->virtual_account_bank}).");
    }

    public function checkOut(Request $request, string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel = $this->hotel();
        $booking = $hotel->bookings()->with(['room', 'guest'])->findOrFail($booking);

        if ($booking->status !== 'checked_in') {
            return back()->withErrors(['booking' => 'Only checked-in bookings can be checked out.']);
        }

        if ($booking->balance > 0 && ! $request->boolean('manager_override')) {
            return back()->withErrors(['balance' => 'Outstanding balance of ₦'.number_format($booking->balanceNaira(), 2).' must be settled, or a manager must override with a reason.']);
        }

        if ($booking->balance > 0 && $request->boolean('manager_override')) {
            if (! in_array(Auth::user()->role, ['owner', 'manager'])) {
                return back()->withErrors(['balance' => 'Only an owner or manager can override an outstanding balance.']);
            }
            $request->validate(['override_reason' => ['required', 'string', 'max:255']]);
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'checked_out', 'checked_out_at' => now()]);
            $booking->room->update(['status' => 'dirty']);
        });

        $this->createHousekeepingTask($booking);

        $overrideNote = $request->boolean('manager_override') ? " Checked out with manager override (balance ₦".number_format($booking->balanceNaira(), 2)."): {$request->input('override_reason')}." : '';

        ActivityLog::record($hotel->id, Auth::user(), 'CHECK_OUT', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." checked out {$booking->guest->name} from Room {$booking->room->room_number}.{$overrideNote}");

        $receiptMsg = "Thank you for staying at {$hotel->name}! Booking {$booking->booking_reference} — Total ₦".number_format($booking->totalAmountNaira(), 2).", Paid ₦".number_format($booking->amountPaidNaira(), 2).".";

        $this->notify->notify(
            $hotel,
            $booking->guest,
            'check_out',
            $receiptMsg,
            'Your receipt — AfricStay',
            "<p>{$receiptMsg}</p>"
        );

        return redirect()->route('hotel.bookings.show', $booking->id)->with('success', 'Checked out. Receipt generated below.');
    }

    public function cancel(string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel = $this->hotel();
        $booking = $hotel->bookings()->findOrFail($booking);

        if (in_array($booking->status, ['checked_out', 'cancelled'])) {
            return back()->withErrors(['booking' => 'This booking cannot be cancelled.']);
        }

        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        ActivityLog::record($hotel->id, Auth::user(), 'CANCEL_BOOKING', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." cancelled booking {$booking->booking_reference}.");

        return redirect()->route('hotel.bookings.index')->with('success', 'Booking cancelled.');
    }

    protected function generateReference(Hotel $hotel): string
    {
        $shortCode = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $hotel->name), 0, 3)) ?: 'AFS';
        return "AFS-{$shortCode}-".now()->format('Ymd').'-'.Str::upper(Str::random(4));
    }

    /**
     * Closes the loop's first step: checkout -> dirty -> [task assigned] -> cleaned -> verified -> available.
     * Assigns to whichever housekeeper currently has the fewest open (non-verified) tasks.
     * If the hotel has no housekeepers yet, the task is created unassigned and the
     * manager is notified instead — the manager will need to assign it manually.
     */
    protected function createHousekeepingTask(Booking $booking): void
    {
        $hotel = $booking->hotel;

        $housekeeper = $hotel->users()
            ->where('role', 'housekeeper')
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($hk) => HousekeepingTask::where('assigned_to', $hk->id)->where('status', '!=', 'verified')->count())
            ->first();

        $task = HousekeepingTask::create([
            'room_id' => $booking->room_id,
            'hotel_id' => $hotel->id,
            'assigned_to' => $housekeeper?->id,
            'triggered_by' => 'checkout',
            'status' => 'pending',
            'checklist' => HousekeepingTask::defaultChecklistFor($booking->room->type),
        ]);

        if ($housekeeper && $housekeeper->phone) {
            $this->sms->send($housekeeper->phone, "Room {$booking->room->room_number} is ready for cleaning at {$hotel->name}.");
        } elseif (! $housekeeper) {
            $manager = $hotel->users()->whereIn('role', ['manager', 'owner'])->first();
            if ($manager?->phone) {
                $this->sms->send($manager->phone, "Room {$booking->room->room_number} needs cleaning, but no housekeeper is assigned yet. Please assign someone.");
            }
        }
    }
}
