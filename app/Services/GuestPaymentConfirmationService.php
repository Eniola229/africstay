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

            if ($booking->status === 'pending') {
                $booking->update(['status' => 'confirmed']);
            }

            $hotel->creditWalletAfterFee($payment->amount);
        });

        $payment->refresh();
        $booking = $payment->booking;
        $hotel = $payment->hotel;

        if ($wasOnlineDepositConfirming) {
            $this->notify->notify(
                $hotel,
                $booking->guest,
                'booking_confirmed',
                "Your booking at {$hotel->name} is confirmed! Ref: {$booking->booking_reference}. Room {$booking->room->room_number}, {$booking->check_in->format('d M Y')} - {$booking->check_out->format('d M Y')}.",
                'Your AfricStay booking is confirmed',
                "<p>Your booking at {$hotel->name} is confirmed. Reference: <strong>{$booking->booking_reference}</strong>.</p>"
            );

            $this->notify->notifyHotel(
                $hotel,
                'new_online_booking',
                "New online booking {$booking->booking_reference} from {$booking->guest->name} — Room {$booking->room->room_number}, {$booking->check_in->format('d M Y')} to {$booking->check_out->format('d M Y')}.",
                'New online booking received',
                "<p>New online booking <strong>{$booking->booking_reference}</strong> from {$booking->guest->name}.</p>"
            );
        } else {
            $this->notify->notify(
                $hotel,
                $booking->guest,
                'payment_received',
                "Payment of ₦".number_format($payment->amount / 100, 2)." received for booking {$booking->booking_reference}. Balance: ₦".number_format($booking->balanceNaira(), 2).".",
                'Payment received — AfricStay',
                "<p>We received your payment of ₦".number_format($payment->amount / 100, 2)." for booking {$booking->booking_reference}.</p>"
            );
        }
    }
}
