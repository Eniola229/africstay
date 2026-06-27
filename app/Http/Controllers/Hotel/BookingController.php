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
use Carbon\Carbon;
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

    /**
     * Return rooms of a given type that are free for the requested datetime window.
     */
    public function availableRooms(Request $request)
    {
        $validated = $request->validate([
            'type'       => ['required', 'in:standard,deluxe,suite,family'],
            'check_in'   => ['required', 'date'],
            'check_out'  => ['required', 'date', 'after:check_in'],
        ]);

        $rooms = $this->hotel()->rooms()
            ->where('type', $validated['type'])
            ->where('status', 'available')
            ->get()
            ->reject(fn (Room $room) => Booking::hasOverlap($room->id, $validated['check_in'], $validated['check_out']))
            ->values();

        return response()->json($rooms->map(fn ($r) => [
            'id'                   => $r->id,
            'room_number'          => $r->room_number,
            'pricing_unit'         => $r->pricing_unit ?? 'night',
            'price_per_night'      => $r->price_per_night,
            'price_per_night_naira'=> $r->pricePerNightNaira(),
        ]));
    }

    public function store(Request $request)
    {
        $this->authorizeBookingStaff();
        $hotel = $this->hotel();

        $validated = $request->validate([
            'guest_id'     => ['nullable', 'uuid'],
            'guest_name'   => ['required_without:guest_id', 'string', 'max:150'],
            'guest_phone'  => ['nullable', 'string', 'max:20'],
            'guest_email'  => ['nullable', 'email', 'max:150'],
            'room_id'      => ['required', 'uuid'],
            'check_in'     => ['required', 'date'],
            'check_out'    => ['required', 'date', 'after:check_in'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $room = $hotel->rooms()->findOrFail($validated['room_id']);

        if ($room->status !== 'available') {
            return back()->withErrors(['room_id' => "Room {$room->room_number} is not currently available."])->withInput();
        }

        if (Booking::hasOverlap($room->id, $validated['check_in'], $validated['check_out'])) {
            return back()->withErrors(['room_id' => "Room {$room->room_number} is already booked for those dates/times."])->withInput();
        }

        $booking = DB::transaction(function () use ($validated, $hotel, $room) {
            if (! empty($validated['guest_id'])) {
                $guest = $hotel->guests()->findOrFail($validated['guest_id']);
            } else {
                $guest = Guest::create([
                    'hotel_id' => $hotel->id,
                    'name'     => $validated['guest_name'],
                    'phone'    => $validated['guest_phone'] ?? null,
                    'email'    => $validated['guest_email'] ?? null,
                ]);
            }

            $checkIn  = Carbon::parse($validated['check_in']);
            $checkOut = Carbon::parse($validated['check_out']);

            $pricingUnit = $room->pricing_unit ?? 'night';

            // Calculate units and total based on pricing unit
            [$units, $total] = $this->calculateUnitsAndTotal($checkIn, $checkOut, $room);

            $reference = $this->generateReference($hotel);

            $booking = Booking::create([
                'hotel_id'          => $hotel->id,
                'room_id'           => $room->id,
                'guest_id'          => $guest->id,
                'receptionist_id'   => Auth::id(),
                'booking_reference' => $reference,
                'check_in'          => $checkIn,
                'check_out'         => $checkOut,
                'nights'            => $units,
                'pricing_unit'      => $pricingUnit,
                'total_amount'      => $total,
                'amount_paid'       => 0,
                'balance'           => $total,
                'status'            => 'confirmed',
                'booking_source'    => 'walk_in',
                'notes'             => $validated['notes'] ?? null,
            ]);

            ActivityLog::record(
                $hotel->id, Auth::user(), 'CREATE_BOOKING', 'booking', 'Booking', $booking->id,
                $booking->booking_reference,
                Auth::user()->name." created booking {$booking->booking_reference} for {$guest->name} — Room {$room->room_number}, {$units} {$pricingUnit}(s)."
            );

            return $booking;
        });

        $booking->load('guest', 'room');

        $this->notify->notify(
            $hotel,
            $booking->guest,
            'booking_confirmed',
            "Dear {$booking->guest->name}, your booking at {$hotel->name} for Room {$booking->room->room_number} on {$booking->check_in->format('d M Y H:i')} is confirmed. Ref: {$booking->booking_reference}.",
            'Your AfricStay booking is confirmed',
            $this->emailBookingConfirmed($hotel, $booking)
        );

        return redirect()->route('hotel.bookings.show', $booking->id)->with('success', "Booking {$booking->booking_reference} created.");
    }

    public function show(string $booking)
    {
        $booking = $this->hotel()->bookings()->with(['room.media', 'guest', 'payments', 'roomServiceOrders.item'])->findOrFail($booking);
        return view('hotel.bookings.show', ['booking' => $booking]);
    }

    public function checkIn(Request $request, string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel   = $this->hotel();
        $booking = $hotel->bookings()->with(['room', 'guest'])->findOrFail($booking);

        if ($booking->status !== 'confirmed') {
            return back()->withErrors(['booking' => 'Only confirmed bookings can be checked in.']);
        }

        $validated = $request->validate([
            'id_type'   => ['required', 'in:nin,passport,drivers_license,other'],
            'id_number' => ['required', 'string', 'max:100'],
        ]);

        $booking->guest->update(['id_type' => $validated['id_type'], 'id_number' => $validated['id_number']]);

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'checked_in', 'checked_in_at' => now()]);
            $booking->room->update(['status' => 'occupied']);
        });

        $payment = $this->virtualAccounts->generate($booking);

        ActivityLog::record(
            $hotel->id, Auth::user(), 'CHECK_IN', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." checked in guest {$booking->guest->name} to Room {$booking->room->room_number}. Booking ref: {$booking->booking_reference}."
        );

        $accountMsg = "Payment account for your stay: {$payment->virtual_account_number} ({$payment->virtual_account_bank}), amount due ₦".number_format($payment->amountNaira(), 2).". Ref: {$booking->booking_reference}.";

        $this->notify->notify(
            $hotel,
            $booking->guest,
            'check_in',
            $accountMsg,
            'Your payment details — AfricStay',
            $this->emailCheckIn($hotel, $booking, $payment, $accountMsg)
        );

        return redirect()->route('hotel.bookings.show', $booking->id)
            ->with('success', "Checked in. Payment account: {$payment->virtual_account_number} ({$payment->virtual_account_bank}).");
    }

    public function checkOut(Request $request, string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel   = $this->hotel();
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

        $overrideNote = $request->boolean('manager_override')
            ? " Checked out with manager override (balance ₦".number_format($booking->balanceNaira(), 2)."): {$request->input('override_reason')}."
            : '';

        ActivityLog::record(
            $hotel->id, Auth::user(), 'CHECK_OUT', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." checked out {$booking->guest->name} from Room {$booking->room->room_number}.{$overrideNote}"
        );

        $receiptMsg = "Thank you for staying at {$hotel->name}! Booking {$booking->booking_reference} — Total ₦".number_format($booking->totalAmountNaira(), 2).", Paid ₦".number_format($booking->amountPaidNaira(), 2).".";

        $this->notify->notify(
            $hotel,
            $booking->guest,
            'check_out',
            $receiptMsg,
            'Your receipt — AfricStay',
            $this->emailCheckOut($hotel, $booking, $receiptMsg)
        );

        return redirect()->route('hotel.bookings.show', $booking->id)->with('success', 'Checked out. Receipt generated below.');
    }

    public function cancel(string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel   = $this->hotel();
        $booking = $hotel->bookings()->findOrFail($booking);

        if (in_array($booking->status, ['checked_out', 'cancelled'])) {
            return back()->withErrors(['booking' => 'This booking cannot be cancelled.']);
        }

        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        ActivityLog::record(
            $hotel->id, Auth::user(), 'CANCEL_BOOKING', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." cancelled booking {$booking->booking_reference}."
        );

        return redirect()->route('hotel.bookings.index')->with('success', 'Booking cancelled.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function generateReference(Hotel $hotel): string
    {
        $shortCode = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $hotel->name), 0, 3)) ?: 'AFS';
        return "AFS-{$shortCode}-".now()->format('Ymd').'-'.Str::upper(Str::random(4));
    }

    /**
     * Calculate the number of billable units and total amount in kobo.
     * Returns [int $units, int $totalKobo].
     */
    protected function calculateUnitsAndTotal(Carbon $checkIn, Carbon $checkOut, Room $room): array
    {
        $pricingUnit  = $room->pricing_unit ?? 'night';
        $pricePerUnit = $room->price_per_night; // stored in kobo

        switch ($pricingUnit) {
            case 'hour':
                $units = max(1, (int) ceil($checkIn->diffInHours($checkOut)));
                break;
            case 'day24':
                // Rolling 24-hour blocks, ceiling
                $hours = $checkIn->diffInMinutes($checkOut) / 60;
                $units = max(1, (int) ceil($hours / 24));
                break;
            case 'night':
            default:
                $units = max(1, $checkIn->diffInDays($checkOut));
                break;
        }

        return [$units, $units * $pricePerUnit];
    }

    /**
     * Closes the checkout loop: checkout → dirty → [task assigned] → cleaned → verified → available.
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

        HousekeepingTask::create([
            'room_id'     => $booking->room_id,
            'hotel_id'    => $hotel->id,
            'assigned_to' => $housekeeper?->id,
            'triggered_by'=> 'checkout',
            'status'      => 'pending',
            'checklist'   => HousekeepingTask::defaultChecklistFor($booking->room->type),
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

    // -------------------------------------------------------------------------
    // Styled email templates
    // -------------------------------------------------------------------------

    protected function emailBase(string $preheader, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AfricStay</title>
<style>
  body{margin:0;padding:0;background:#f4f6f9;font-family:'Helvetica Neue',Arial,sans-serif;color:#333}
  .wrapper{max-width:600px;margin:32px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .header{background:linear-gradient(135deg,#1a5276 0%,#2e86c1 100%);padding:32px 40px;text-align:center}
  .header img{height:40px;margin-bottom:8px}
  .header h1{color:#fff;margin:0;font-size:22px;font-weight:700;letter-spacing:.5px}
  .header p{color:rgba(255,255,255,.85);margin:4px 0 0;font-size:13px}
  .body{padding:36px 40px}
  .body p{margin:0 0 14px;line-height:1.7;font-size:15px}
  .body h2{font-size:17px;margin:0 0 18px;color:#1a5276}
  .info-table{width:100%;border-collapse:collapse;margin:20px 0}
  .info-table td{padding:10px 14px;font-size:14px;border-bottom:1px solid #eef0f3}
  .info-table td:first-child{color:#666;width:42%;font-weight:600}
  .info-table tr:last-child td{border-bottom:none}
  .badge{display:inline-block;background:#eaf4fb;color:#1a5276;border-radius:20px;padding:4px 14px;font-size:13px;font-weight:700;letter-spacing:.5px}
  .btn{display:inline-block;margin:22px 0 8px;padding:13px 32px;background:#2e86c1;color:#fff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:600}
  .divider{border:none;border-top:1px solid #eef0f3;margin:24px 0}
  .footer{background:#f4f6f9;padding:22px 40px;text-align:center}
  .footer p{color:#999;font-size:12px;margin:4px 0;line-height:1.6}
  .footer a{color:#2e86c1;text-decoration:none}
</style>
</head>
<body>
<span style="display:none;max-height:0;overflow:hidden">{$preheader}</span>
<div class="wrapper">
  <div class="header">
    <h1>AfricStay</h1>
    <p>Hotel Management Made Simple</p>
  </div>
  <div class="body">
    {$body}
  </div>
  <div class="footer">
    <p>AfricStay Hotel Management System</p>
    <p>Need help? <a href="mailto:support@africstayhms.com">support@africstayhms.com</a></p>
    <p style="margin-top:10px;color:#ccc">© AfricStay. All rights reserved.</p>
  </div>
</div>
</body>
</html>
HTML;
    }

    protected function emailBookingConfirmed(Hotel $hotel, Booking $booking): string
    {
        $unit  = $booking->pricing_unit ?? 'night';
        $label = match($unit) { 'hour' => 'hour(s)', 'day24' => '24-hour block(s)', default => 'night(s)' };

        $body = "
        <h2>Booking Confirmed! 🎉</h2>
        <p>Dear <strong>{$booking->guest->name}</strong>,</p>
        <p>Your reservation at <strong>{$hotel->name}</strong> has been confirmed. Here are your booking details:</p>
        <table class='info-table'>
          <tr><td>Booking Ref</td><td><span class='badge'>{$booking->booking_reference}</span></td></tr>
          <tr><td>Room</td><td>Room {$booking->room->room_number} (".ucfirst($booking->room->type).")</td></tr>
          <tr><td>Check-in</td><td>{$booking->check_in->format('D, d M Y — H:i')}</td></tr>
          <tr><td>Check-out</td><td>{$booking->check_out->format('D, d M Y — H:i')}</td></tr>
          <tr><td>Duration</td><td>{$booking->nights} {$label}</td></tr>
          <tr><td>Total Amount</td><td>₦".number_format($booking->totalAmountNaira(), 2)."</td></tr>
        </table>
        <hr class='divider'>
        <p>Please keep your booking reference safe — you'll need it at check-in.</p>
        <p>We look forward to welcoming you!</p>";

        return $this->emailBase("Your booking at {$hotel->name} is confirmed. Ref: {$booking->booking_reference}", $body);
    }

    protected function emailCheckIn(Hotel $hotel, Booking $booking, $payment, string $fallback): string
    {
        $body = "
        <h2>Welcome! You're Checked In ✅</h2>
        <p>Dear <strong>{$booking->guest->name}</strong>,</p>
        <p>You've been successfully checked in at <strong>{$hotel->name}</strong>. Please use the payment details below to complete your payment:</p>
        <table class='info-table'>
          <tr><td>Booking Ref</td><td><span class='badge'>{$booking->booking_reference}</span></td></tr>
          <tr><td>Room</td><td>Room {$booking->room->room_number}</td></tr>
          <tr><td>Bank</td><td>{$payment->virtual_account_bank}</td></tr>
          <tr><td>Account Number</td><td><strong style='font-size:18px;letter-spacing:2px'>{$payment->virtual_account_number}</strong></td></tr>
          <tr><td>Amount Due</td><td><strong>₦".number_format($payment->amountNaira(), 2)."</strong></td></tr>
        </table>
        <hr class='divider'>
        <p style='font-size:13px;color:#666'>Payment is credited automatically once received. Contact the front desk if you have any issues.</p>
        <p>Enjoy your stay! 🏨</p>";

        return $this->emailBase("Payment details for your stay at {$hotel->name}", $body);
    }

    protected function emailCheckOut(Hotel $hotel, Booking $booking, string $fallback): string
    {
        $balance = $booking->balanceNaira();

        $body = "
        <h2>Thank You for Staying With Us 🙏</h2>
        <p>Dear <strong>{$booking->guest->name}</strong>,</p>
        <p>We hope you had a wonderful stay at <strong>{$hotel->name}</strong>. Here is your receipt summary:</p>
        <table class='info-table'>
          <tr><td>Booking Ref</td><td><span class='badge'>{$booking->booking_reference}</span></td></tr>
          <tr><td>Room</td><td>Room {$booking->room->room_number}</td></tr>
          <tr><td>Check-in</td><td>{$booking->check_in->format('D, d M Y — H:i')}</td></tr>
          <tr><td>Check-out</td><td>{$booking->check_out->format('D, d M Y — H:i')}</td></tr>
          <tr><td>Total Amount</td><td>₦".number_format($booking->totalAmountNaira(), 2)."</td></tr>
          <tr><td>Amount Paid</td><td>₦".number_format($booking->amountPaidNaira(), 2)."</td></tr>
          <tr><td>Balance</td><td>".($balance > 0 ? "<span style='color:#c0392b;font-weight:bold'>₦".number_format($balance, 2)." (Outstanding)</span>" : "<span style='color:#27ae60;font-weight:bold'>Fully Paid</span>")."</td></tr>
        </table>
        <hr class='divider'>
        <p>We hope to see you again soon! For any queries, contact us at <a href='mailto:support@africstayhms.com'>support@africstayhms.com</a>.</p>";

        return $this->emailBase("Your receipt from {$hotel->name} — Ref {$booking->booking_reference}", $body);
    }

    public function availableRooms(Request $request)
    {
        $validated = $request->validate([
            'type'      => ['required', 'in:standard,deluxe,suite,family'],
            'check_in'  => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);
     
        $hotel = $this->hotel();
     
        $rooms = $hotel->rooms()
            ->where('type', $validated['type'])
            ->where('status', 'available')
            ->get()
            ->reject(fn (Room $room) => Booking::hasOverlap(
                $room->id,
                $validated['check_in'],
                $validated['check_out']
            ))
            ->values();
     
        return response()->json(
            $rooms->map(fn ($r) => [
                'id'                    => $r->id,
                'room_number'           => $r->room_number,
                'pricing_unit'          => $r->pricing_unit ?? 'night',
                'price_per_night'       => $r->price_per_night,          // kobo
                'price_per_night_naira' => $r->pricePerNightNaira(),     // naira (float)
            ])
        );
    }
}