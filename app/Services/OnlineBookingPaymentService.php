<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Initiates the guest-facing deposit checkout for an ONLINE booking
 * (distinct from VirtualAccountService, which is for the in-person
 * check-in payment account). This is a hosted Flutterwave/Paystack
 * checkout page the guest pays on directly — same provider-fallback rule,
 * same AFS-PAY- reference prefix so the existing webhook routing in
 * FlutterwaveWebhookController / PaystackWebhookController picks it up
 * with zero changes there.
 */
class OnlineBookingPaymentService
{
    public function initiateDepositCheckout(Booking $booking, int $depositAmountKobo): array
    {
        $reference = 'AFS-PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        $guest = $booking->guest;
        $hotel = $booking->hotel;

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'hotel_id' => $hotel->id,
            'amount' => $depositAmountKobo,
            'payment_method' => 'card',
            'provider' => 'flutterwave',
            'payment_reference' => $reference,
            'status' => 'pending',
        ]);

        $checkoutUrl = $this->tryFlutterwave($booking, $payment, $depositAmountKobo, $reference);

        if (! $checkoutUrl) {
            $payment->update(['provider' => 'paystack']);
            $checkoutUrl = $this->tryPaystack($booking, $payment, $depositAmountKobo, $reference);
        }

        if (! $checkoutUrl) {
            $payment->update(['status' => 'failed']);
            throw new \RuntimeException('Could not start payment with either provider. Please try again shortly.');
        }

        return ['checkout_url' => $checkoutUrl, 'payment' => $payment];
    }

    protected function tryFlutterwave(Booking $booking, Payment $payment, int $amountKobo, string $reference): ?string
    {
        $key = config('services.flutterwave.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            $response = Http::withToken($key)->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $reference,
                'amount' => $amountKobo / 100,
                'currency' => 'NGN',
                'redirect_url' => route('public.booking.callback', $booking->hotel->slug),
                'customer' => [
                    'email' => $booking->guest->email ?? $booking->hotel->email ?? 'guest@africstayhms.com',
                    'phonenumber' => $booking->guest->phone ?? $booking->hotel->phone,
                    'name' => $booking->guest->name,
                ],
                'customizations' => [
                    'title' => $booking->hotel->name.' — Booking Deposit',
                    'description' => "Deposit for booking {$booking->booking_reference}",
                ],
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json('data.link');
            }

            Log::warning('Flutterwave deposit checkout init failed — falling back to Paystack.', ['booking' => $booking->id]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Flutterwave deposit init exception: '.$e->getMessage());
            return null;
        }
    }

    protected function tryPaystack(Booking $booking, Payment $payment, int $amountKobo, string $reference): ?string
    {
        $key = config('services.paystack.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            $response = Http::withToken($key)->post('https://api.paystack.co/transaction/initialize', [
                'email' => $booking->guest->email ?? $booking->hotel->email ?? 'guest@africstayhms.com',
                'amount' => $amountKobo,
                'reference' => $reference,
                'callback_url' => route('public.booking.callback', $booking->hotel->slug),
            ]);

            if ($response->successful() && $response->json('status') === true) {
                return $response->json('data.authorization_url');
            }

            Log::error('Paystack deposit checkout init also failed.', ['booking' => $booking->id]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Paystack deposit init exception: '.$e->getMessage());
            return null;
        }
    }
}
