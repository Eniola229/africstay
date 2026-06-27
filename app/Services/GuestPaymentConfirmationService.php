<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Called by the webhook controllers once a payment is confirmed — covers
 * BOTH guest-payment flows that share the AFS-PAY- reference prefix:
 *
 *  1. Check-in virtual account (VirtualAccountService) — booking is already
 *     `checked_in` by the time this fires; we just credit the balance.
 *  2. Online booking deposit (OnlineBookingPaymentService) — booking is
 *     still `pending`; this is what actually CONFIRMS the booking and
 *     reserves the room for real, so it gets a different notification
 *     ("booking confirmed", not "payment received").
 *
 * Idempotent on payment_reference either way — a retried webhook can never
 * credit/confirm the same payment twice.
 */
class GuestPaymentConfirmationService
{
    public function __construct(protected NotificationFallbackService $notify) {}

    public function confirm(string $paymentReference, string $providerReference): void
    {
        $payment = Payment::where('payment_reference', $paymentReference)->first();

        if (! $payment || $payment->status === 'confirmed') {
            return;
        }

        $wasOnlineDepositConfirming = $payment->booking->status === 'pending';

        DB::transaction(function () use ($payment, $providerReference) {
            $payment->update([
                'status' => 'confirmed',
                'provider_reference' => $providerReference,
                'paid_at' => now(),
            ]);

            $booking = $payment->booking;
            $hotel = $payment->hotel;

            $booking->increment('amount_paid', $payment->amount);
            $booking->update(['balance' => max(0, $booking->total_amount - $booking->amount_paid)]);

            // Update to checked_in if:
            // 1. Booking is confirmed AND full amount is paid (walk-in with virtual account)
            // 2. Booking is online (booking_source === 'online') AND full amount is paid
            $shouldCheckIn = false;
            
            if ($booking->status === 'confirmed' && $booking->balance <= 0) {
                $shouldCheckIn = true;
            } elseif ($booking->booking_source === 'online' && $booking->balance <= 0) {
                $shouldCheckIn = true;
            }

            if ($shouldCheckIn) {
                $booking->update([
                    'status' => 'checked_in',
                    'checked_in_at' => now()
                ]);
                // Update room status to occupied since they've paid and checked in
                $booking->room->update(['status' => 'occupied']);
            }

            $hotel->creditWalletAfterFee($payment->amount);
        });

        $payment->refresh();
        $booking = $payment->booking;
        $hotel = $payment->hotel;

        if ($wasOnlineDepositConfirming) {
            // Send booking confirmed email to guest - using hotel name in subject
            $this->notify->notify(
                $hotel,
                $booking->guest,
                'booking_confirmed',
                "Your booking at {$hotel->name} is confirmed! Ref: {$booking->booking_reference}. Room {$booking->room->room_number}, {$booking->check_in->format('d M Y')} - {$booking->check_out->format('d M Y')}.",
                "Your booking at {$hotel->name} is confirmed",
                $this->emailBookingConfirmed($hotel, $booking)
            );

            // Send notification to hotel staff
            $this->notify->notifyHotel(
                $hotel,
                'new_online_booking',
                "New online booking {$booking->booking_reference} from {$booking->guest->name} — Room {$booking->room->room_number}, {$booking->check_in->format('d M Y')} to {$booking->check_out->format('d M Y')}.",
                "New online booking at {$hotel->name}",
                $this->emailHotelNewBooking($hotel, $booking)
            );
        } else {
            // Send payment received email to guest - using hotel name in subject
            $this->notify->notify(
                $hotel,
                $booking->guest,
                'payment_received',
                "Payment of ₦".number_format($payment->amount / 100, 2)." received for booking {$booking->booking_reference}. Balance: ₦".number_format($booking->balanceNaira(), 2).".",
                "Payment received for your stay at {$hotel->name}",
                $this->emailPaymentReceived($hotel, $booking, $payment)
            );
        }
    }

    // ─── STYLED EMAIL TEMPLATES ────────────────────────────────────────────────

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
    .info-table{width:100%;border-collapse:collapse;margin:20px 0;background:#f8faf8;border-radius:8px;overflow:hidden;}
    .info-table td{padding:12px 16px;font-size:14px;border-bottom:1px solid #e8efe8;}
    .info-table td:first-child{color:#4a6a4a;font-weight:600;width:45%;}
    .info-table tr:last-child td{border-bottom:none;}
    .badge{display:inline-block;background:#f0f5f0;color:#0a3622;border-radius:20px;padding:4px 14px;font-size:13px;font-weight:600;letter-spacing:0.5px;}
    .amount{font-size:18px;font-weight:700;color:#0a3622;}
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

    protected function emailBookingConfirmed($hotel, $booking): string
    {
        $unit = $booking->pricing_unit ?? 'night';
        $label = match($unit) { 
            'hour' => 'hour(s)', 
            'day24' => '24-hour block(s)', 
            default => 'night(s)' 
        };

        $body = "
        <h2>✅ Booking confirmed</h2>
        
        <p style='margin:0 0 16px;'>Dear <strong style='color:#0a3622;'>{$booking->guest->name}</strong>,</p>
        
        <p style='margin:0 0 16px;'>Your reservation at <strong style='color:#0a3622;'>{$hotel->name}</strong> is confirmed. Here's everything you need to know:</p>
        
        <table class='info-table'>
            <tr>
                <td>Booking reference</td>
                <td><span class='badge'>{$booking->booking_reference}</span></td>
            </tr>
            <tr>
                <td>Room</td>
                <td>Room {$booking->room->room_number} (".ucfirst($booking->room->type).")</td>
            </tr>
            <tr>
                <td>Check-in</td>
                <td>{$booking->check_in->format('D, d M Y — H:i')}</td>
            </tr>
            <tr>
                <td>Check-out</td>
                <td>{$booking->check_out->format('D, d M Y — H:i')}</td>
            </tr>
            <tr>
                <td>Duration</td>
                <td>{$booking->nights} {$label}</td>
            </tr>
            <tr>
                <td>Total amount</td>
                <td class='amount'>₦".number_format($booking->totalAmountNaira(), 2)."</td>
            </tr>
            <tr>
                <td>Amount paid</td>
                <td class='amount'>₦".number_format($booking->amountPaidNaira(), 2)."</td>
            </tr>
        </table>
        
        <hr class='divider'>
        
        <p style='font-size:13px;color:#6a8a6a;margin:0;'>Keep your booking reference safe — you'll need it at check-in.</p>
        <p style='font-size:13px;color:#6a8a6a;margin:4px 0 0;'>We look forward to welcoming you!</p>";

        return $this->emailBase("Your booking at {$hotel->name} is confirmed", $body, $hotel->name);
    }

    protected function emailHotelNewBooking($hotel, $booking): string
    {
        $body = "
        <h2>🆕 New online booking</h2>
        
        <p style='margin:0 0 16px;'>Hi <strong style='color:#0a3622;'>{$hotel->name}</strong> team,</p>
        
        <p style='margin:0 0 16px;'>A new online booking has been received and confirmed. Here are the details:</p>
        
        <table class='info-table'>
            <tr>
                <td>Booking reference</td>
                <td><span class='badge'>{$booking->booking_reference}</span></td>
            </tr>
            <tr>
                <td>Guest</td>
                <td>{$booking->guest->name}</td>
            </tr>
            <tr>
                <td>Room</td>
                <td>Room {$booking->room->room_number} (".ucfirst($booking->room->type).")</td>
            </tr>
            <tr>
                <td>Check-in</td>
                <td>{$booking->check_in->format('D, d M Y — H:i')}</td>
            </tr>
            <tr>
                <td>Check-out</td>
                <td>{$booking->check_out->format('D, d M Y — H:i')}</td>
            </tr>
            <tr>
                <td>Total amount</td>
                <td class='amount'>₦".number_format($booking->totalAmountNaira(), 2)."</td>
            </tr>
            <tr>
                <td>Amount paid</td>
                <td class='amount'>₦".number_format($booking->amountPaidNaira(), 2)."</td>
            </tr>
        </table>
        
        <hr class='divider'>
        
        <p style='font-size:13px;color:#6a8a6a;margin:0;'>Please prepare the room for the guest's arrival.</p>";

        return $this->emailBase("New online booking received", $body, $hotel->name);
    }

    protected function emailPaymentReceived($hotel, $booking, $payment): string
    {
        $body = "
        <h2>✅ Payment received</h2>
        
        <p style='margin:0 0 16px;'>Dear <strong style='color:#0a3622;'>{$booking->guest->name}</strong>,</p>
        
        <p style='margin:0 0 16px;'>We've received your payment. Here's your updated payment summary:</p>
        
        <table class='info-table'>
            <tr>
                <td>Booking reference</td>
                <td><span class='badge'>{$booking->booking_reference}</span></td>
            </tr>
            <tr>
                <td>Amount received</td>
                <td class='amount'>₦".number_format($payment->amount / 100, 2)."</td>
            </tr>
            <tr>
                <td>Total amount</td>
                <td>₦".number_format($booking->totalAmountNaira(), 2)."</td>
            </tr>
            <tr>
                <td>Balance</td>
                <td>".($booking->balanceNaira() > 0 ? "₦".number_format($booking->balanceNaira(), 2) : "<span style='color:#0a3622;font-weight:600;'>Fully paid ✓</span>")."</td>
            </tr>
        </table>
        
        <hr class='divider'>
        
        <p style='font-size:13px;color:#6a8a6a;margin:0;'>Thank you for choosing {$hotel->name}.</p>
        <p style='font-size:13px;color:#6a8a6a;margin:4px 0 0;'>Questions? <a href='mailto:support@africstayhms.com' style='color:#0a3622;text-decoration:underline;'>support@africstayhms.com</a></p>";

        return $this->emailBase("Payment received for your booking", $body, $hotel->name);
    }
}