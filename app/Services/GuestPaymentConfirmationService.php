<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Called by the webhook controllers once a transfer into a guest's virtual
 * account is confirmed. Idempotent on payment_reference — a retried webhook
 * can never credit the same payment twice (spec: webhook idempotency).
 */
class GuestPaymentConfirmationService
{
    public function __construct(protected NotificationFallbackService $notify) {}

    public function confirm(string $paymentReference, string $providerReference): void
    {
        $payment = Payment::where('payment_reference', $paymentReference)->first();

        if (! $payment || $payment->status === 'confirmed') {
            return; // unknown reference, or already processed
        }

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

            $hotel->creditWalletAfterFee($payment->amount);
        });

        $payment->refresh();
        $booking = $payment->booking;
        $hotel = $payment->hotel;

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
