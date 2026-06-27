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
        $query = $this->hotel()->bookings()->with(['room', 'guest', 'paymentAccount'])->latest();

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

      public function store(Request $request)
    {
        $this->authorizeBookingStaff();
        $hotel = $this->hotel();

        $validated = $request->validate([
            'guest_id'     => ['nullable', 'uuid', 'exists:guests,id'],
            'guest_name'   => ['required_without:guest_id', 'string', 'max:150'], // Only required if no guest_id
            'guest_phone'  => ['nullable', 'string', 'max:20'],
            'guest_email'  => ['nullable', 'email', 'max:150'],
            'room_id'      => ['required', 'uuid', 'exists:rooms,id'],
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

            try {
                $payment = $this->virtualAccounts->generate($booking);
                $booking->load('paymentAccount');
            } catch (\Exception $e) {
                \Log::error('Virtual account creation failed for booking: ' . $booking->id, [
                    'error' => $e->getMessage(),
                    'booking_reference' => $booking->booking_reference
                ]);
            }

            ActivityLog::record(
                $hotel->id, Auth::user(), 'CREATE_BOOKING', 'booking', 'Booking', $booking->id,
                $booking->booking_reference,
                Auth::user()->name." created booking {$booking->booking_reference} for {$guest->name} — Room {$room->room_number}, {$units} {$pricingUnit}(s)."
            );

            return $booking;
        });

        $booking->load(['guest', 'room', 'paymentAccount']);

        $paymentAccount = $booking->paymentAccount;
        $paymentMsg = "";
        
        if ($paymentAccount) {
            $paymentMsg = " Payment: {$paymentAccount->virtual_account_number} ({$paymentAccount->virtual_account_bank}), amount ₦" . number_format($paymentAccount->amountNaira(), 2);
        }

        // Send email to guest with booking confirmation and payment details
        $this->notify->notify(
            $hotel,
            $booking->guest,
            'booking_confirmed',
            "Dear {$booking->guest->name}, your booking at {$hotel->name} for Room {$booking->room->room_number} on {$booking->check_in->format('d M Y H:i')} is confirmed. Ref: {$booking->booking_reference}.{$paymentMsg}",
            'Your booking is confirmed',
            $this->emailBookingConfirmed($hotel, $booking)
        );

        return redirect()->route('hotel.bookings.show', $booking->id)
            ->with('success', "Booking {$booking->booking_reference} created. " . ($paymentAccount ? "Payment account: {$paymentAccount->virtual_account_number} ({$paymentAccount->virtual_account_bank})" : "Payment details will be generated at check-in."));
    }
    public function show(string $booking)
    {
        $booking = $this->hotel()->bookings()->with([
            'room.media', 
            'guest', 
            'payments', 
            'roomServiceOrders.item',
            'paymentAccount'
        ])->findOrFail($booking);
        
        return view('hotel.bookings.show', ['booking' => $booking]);
    }

    public function checkIn(Request $request, string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel   = $this->hotel();
        $booking = $hotel->bookings()->with(['room', 'guest', 'paymentAccount'])->findOrFail($booking);

        if ($booking->status !== 'confirmed') {
            return back()->withErrors(['booking' => 'Only confirmed bookings can be checked in.']);
        }

        $validated = $request->validate([
            'id_type'   => ['nullable', 'in:nin,passport,drivers_license,other'],
            'id_number' => ['nullable', 'string', 'max:100'],
        ]);

        if (!empty($validated['id_type']) && !empty($validated['id_number'])) {
            $booking->guest->update([
                'id_type' => $validated['id_type'], 
                'id_number' => $validated['id_number']
            ]);
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'checked_in', 'checked_in_at' => now()]);
            $booking->room->update(['status' => 'occupied']);
        });

        $payment = $booking->paymentAccount;
        if (!$payment) {
            try {
                $payment = $this->virtualAccounts->generate($booking);
                $booking->load('paymentAccount');
            } catch (\Exception $e) {
                \Log::error('Virtual account creation failed during check-in: ' . $booking->id, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        ActivityLog::record(
            $hotel->id, Auth::user(), 'CHECK_IN', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." checked in guest {$booking->guest->name} to Room {$booking->room->room_number}. Booking ref: {$booking->booking_reference}."
        );

        $accountMsg = "Payment account for your stay";
        if ($payment) {
            $accountMsg = "Payment account for your stay: {$payment->virtual_account_number} ({$payment->virtual_account_bank}), amount due ₦".number_format($payment->amountNaira(), 2).". Ref: {$booking->booking_reference}.";
        }

        $this->notify->notify(
            $hotel,
            $booking->guest,
            'check_in',
            $accountMsg,
            'Your payment details',
            $this->emailCheckIn($hotel, $booking, $payment, $accountMsg)
        );

        $successMsg = "Guest checked in successfully.";
        if ($payment) {
            $successMsg .= " Payment account: {$payment->virtual_account_number} ({$payment->virtual_account_bank}).";
        }

        return redirect()->route('hotel.bookings.show', $booking->id)
            ->with('success', $successMsg);
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
            'Your receipt',
            $this->emailCheckOut($hotel, $booking, $receiptMsg)
        );

        return redirect()->route('hotel.bookings.show', $booking->id)->with('success', 'Checked out. Receipt generated below.');
    }

    public function cancel(string $booking)
    {
        $this->authorizeBookingStaff();
        $hotel   = $this->hotel();
        $booking = $hotel->bookings()->with('room')->findOrFail($booking);

        if (in_array($booking->status, ['checked_out', 'cancelled'])) {
            return back()->withErrors(['booking' => 'This booking cannot be cancelled.']);
        }

        \DB::transaction(function () use ($booking, $hotel) {
            // Store the room ID for logging
            $roomNumber = $booking->room->room_number ?? 'N/A';
            
            // Update booking status
            $booking->update([
                'status' => 'cancelled', 
                'cancelled_at' => now()
            ]);

            // If the room was occupied (checked_in), make it available
            // If it was confirmed, it was never occupied, so just leave it as available
            if ($booking->status === 'checked_in' && $booking->room) {
                $booking->room->update(['status' => 'available']);
            }

            // If there's a housekeeping task for this room, cancel it
            \App\Models\HousekeepingTask::where('room_id', $booking->room_id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        });

        ActivityLog::record(
            $hotel->id, Auth::user(), 'CANCEL_BOOKING', 'booking', 'Booking', $booking->id, $booking->booking_reference,
            Auth::user()->name." cancelled booking {$booking->booking_reference}. Room {$booking->room->room_number} set to available."
        );

        return redirect()->route('hotel.bookings.index')->with('success', 'Booking cancelled successfully. Room is now available.');
    }

    protected function generateReference(Hotel $hotel): string
    {
        $shortCode = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $hotel->name), 0, 3)) ?: 'AFS';
        return "AFS-{$shortCode}-".now()->format('Ymd').'-'.Str::upper(Str::random(4));
    }

    protected function calculateUnitsAndTotal(Carbon $checkIn, Carbon $checkOut, Room $room): array
    {
        $pricingUnit  = $room->pricing_unit ?? 'night';
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

        return [$units, $units * $pricePerUnit];
    }

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

    // ─── HOTEL BRANDED EMAIL TEMPLATES ─────────────────────────────────────────

    protected function emailBase(string $preheader, string $body, string $hotelName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$hotelName}</title>
<style>
    body{margin:0;padding:0;background:#f0f5f0;font-family:'Helvetica Neue',Arial,sans-serif;color:#2d2d2d;}
    .wrapper{max-width:600px;margin:32px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(10,54,34,0.08);}
    .header{background:#0a3622;padding:32px 40px 28px;text-align:center;}
    .header h1{color:#ffffff;margin:0;font-size:24px;font-weight:700;letter-spacing:0.5px;}
    .header p{color:rgba(255,255,255,0.8);margin:6px 0 0;font-size:13px;font-weight:300;letter-spacing:0.3px;}
    .body{padding:36px 40px 28px;}
    .body p{margin:0 0 14px;line-height:1.7;font-size:15px;color:#2d2d2d;}
    .body h2{font-size:20px;margin:0 0 16px;color:#0a3622;font-weight:600;}
    .divider{border:none;border-top:1px solid #e8efe8;margin:24px 0;}
    .footer{background:#f8faf8;padding:20px 40px;text-align:center;border-top:1px solid #e8efe8;}
    .footer p{color:#6a8a6a;font-size:12px;margin:4px 0;line-height:1.6;}
    .footer a{color:#0a3622;text-decoration:underline;}
</style>
</head>
<body>
<span style="display:none;max-height:0;overflow:hidden;">{$preheader}</span>
<div class="wrapper">
    <div class="header">
        <h1>{$hotelName}</h1>
        <p>Powered by AfricStay</p>
    </div>
    <div class="body">
        {$body}
    </div>
    <div class="footer">
        <p>{$hotelName} — Guest experience</p>
        <p><a href="mailto:support@africstayhms.com">support@africstayhms.com</a></p>
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
        
        $payment = $booking->paymentAccount;
        $paymentRow = '';
        if ($payment) {
            $paymentRow = "
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Payment account</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$payment->virtual_account_number} ({$payment->virtual_account_bank})</td>
            </tr>
            <tr>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Amount due</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:600;'>₦".number_format($payment->amountNaira(), 2)."</td>
            </tr>";
        }

        $body = "
        <h2>✅ Booking confirmed</h2>
        
        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Dear <strong style='color:#0a3622;'>{$booking->guest->name}</strong>,</p>
        
        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Your reservation at <strong style='color:#0a3622;'>{$hotel->name}</strong> is confirmed. Here's everything you need to know:</p>
        
        <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f8faf8;border-radius:8px;overflow:hidden;'>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Booking reference</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:600;'>{$booking->booking_reference}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Room</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>Room {$booking->room->room_number} (".ucfirst($booking->room->type).")</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Check-in</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$booking->check_in->format('D, d M Y — H:i')}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Check-out</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$booking->check_out->format('D, d M Y — H:i')}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Duration</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$booking->nights} {$label}</td>
            </tr>
            <tr>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Total amount</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:600;'>₦".number_format($booking->totalAmountNaira(), 2)."</td>
            </tr>
            {$paymentRow}
        </table>
        
        <hr class='divider'>
        
        " . ($payment ? "<p style='font-size:14px;color:#0a3622;font-weight:500;margin:0 0 12px;'>💳 Please transfer the amount above to the provided account number.</p>" : "") . "
        
        <p style='font-size:13px;color:#6a8a6a;margin:0;'>Keep your booking reference safe — you'll need it at check-in.</p>
        <p style='font-size:13px;color:#6a8a6a;margin:4px 0 0;'>We look forward to welcoming you!</p>";

        return $this->emailBase("Your booking at {$hotel->name} is confirmed", $body, $hotel->name);
    }

    protected function emailCheckIn(Hotel $hotel, Booking $booking, $payment, string $fallback): string
    {
        $paymentRow = '';
        if ($payment) {
            $paymentRow = "
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Bank</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$payment->virtual_account_bank}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Account number</td>
                <td style='padding:12px 16px;font-size:16px;color:#0a3622;font-weight:600;letter-spacing:2px;'>{$payment->virtual_account_number}</td>
            </tr>
            <tr>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Amount due</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:600;'>₦".number_format($payment->amountNaira(), 2)."</td>
            </tr>";
        }

        $body = "
        <h2>🏨 Welcome! You're checked in</h2>
        
        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Dear <strong style='color:#0a3622;'>{$booking->guest->name}</strong>,</p>
        
        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>You've been checked in at <strong style='color:#0a3622;'>{$hotel->name}</strong>. " . ($payment ? "Here are your payment details:" : "We hope you enjoy your stay!") . "</p>
        
        <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f8faf8;border-radius:8px;overflow:hidden;'>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Booking reference</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$booking->booking_reference}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Room</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>Room {$booking->room->room_number}</td>
            </tr>
            {$paymentRow}
        </table>
        
        <hr class='divider'>
        
        <p style='font-size:13px;color:#6a8a6a;margin:0;'>Payments are credited automatically once received.</p>
        <p style='font-size:13px;color:#6a8a6a;margin:4px 0 0;'>Enjoy your stay! 🏨</p>";

        return $this->emailBase("Payment details for your stay at {$hotel->name}", $body, $hotel->name);
    }

    protected function emailCheckOut(Hotel $hotel, Booking $booking, string $fallback): string
    {
        $balance = $booking->balanceNaira();

        $body = "
        <h2>🙏 Thank you for staying with us</h2>
        
        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Dear <strong style='color:#0a3622;'>{$booking->guest->name}</strong>,</p>
        
        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>We hope you had a wonderful stay at <strong style='color:#0a3622;'>{$hotel->name}</strong>. Here's your receipt summary:</p>
        
        <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f8faf8;border-radius:8px;overflow:hidden;'>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Booking reference</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$booking->booking_reference}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Room</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>Room {$booking->room->room_number}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Check-in</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$booking->check_in->format('D, d M Y — H:i')}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Check-out</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$booking->check_out->format('D, d M Y — H:i')}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Total amount</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>₦".number_format($booking->totalAmountNaira(), 2)."</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Amount paid</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>₦".number_format($booking->amountPaidNaira(), 2)."</td>
            </tr>
            <tr>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Balance</td>
                <td style='padding:12px 16px;font-size:14px;font-weight:600;'>".($balance > 0 ? "<span style='color:#8a1a1a;'>₦".number_format($balance, 2)." (outstanding)</span>" : "<span style='color:#0a3622;'>Fully paid ✓</span>")."</td>
            </tr>
        </table>
        
        <hr class='divider'>
        
        <p style='font-size:13px;color:#6a8a6a;margin:0;'>We hope to see you again soon!</p>
        <p style='font-size:13px;color:#6a8a6a;margin:4px 0 0;'>Questions? <a href='mailto:support@africstayhms.com' style='color:#0a3622;text-decoration:underline;'>support@africstayhms.com</a></p>";

        return $this->emailBase("Your receipt from {$hotel->name}", $body, $hotel->name);
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
        ->values();
 
    return response()->json(
        $rooms->map(fn ($r) => [
            'id'                    => $r->id,
            'room_number'           => $r->room_number,
            'pricing_unit'          => $r->pricing_unit ?? 'night',
            'price_per_night'       => $r->price_per_night,
            'price_per_night_naira' => $r->pricePerNightNaira(),
        ])
    );
}
public function checkPayment(string $booking)
{
    $booking = $this->hotel()->bookings()->with('payments')->findOrFail($booking);
    
    $latestPayment = $booking->payments->sortByDesc('created_at')->first();
    
    return response()->json([
        'payment_confirmed' => $latestPayment && $latestPayment->status === 'confirmed',
        'payment_status' => $latestPayment ? $latestPayment->status : 'no_payment',
        'balance' => $booking->balance,
        'amount_paid' => $booking->amount_paid,
        'booking_status' => $booking->status,
    ]);
}
}