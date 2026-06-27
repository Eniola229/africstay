<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OnlineBookingPaymentService
{
    protected string $flutterwaveSecretKey;
    protected string $flutterwavePublicKey;
    protected string $paystackSecretKey;
    protected string $paystackPublicKey;

    public function __construct()
    {
        $this->flutterwaveSecretKey = config('services.flutterwave.secret_key');
        $this->flutterwavePublicKey = config('services.flutterwave.public_key');
        $this->paystackSecretKey = config('services.paystack.secret_key');
        $this->paystackPublicKey = config('services.paystack.public_key');
    }

    /**
     * Initiate full payment checkout - Flutterwave first, Paystack as fallback
     */
    public function initiateFullPaymentCheckout(Booking $booking, int $amountInKobo): array
    {
        try {
            return $this->initiateFlutterwavePayment($booking, $amountInKobo);
        } catch (\Exception $e) {
            \Log::warning('Flutterwave failed, switching to Paystack.', [
                'error' => $e->getMessage(),
                'booking' => $booking->id
            ]);

            return $this->initiatePaystackPayment($booking, $amountInKobo);
        }
    }

    /**
     * Initiate Flutterwave payment
     */
    protected function initiateFlutterwavePayment(Booking $booking, int $amountInKobo): array
    {
        $paymentReference = 'AFS-PAY-' . Str::upper(Str::random(10));

        Payment::create([
            'booking_id' => $booking->id,
            'hotel_id' => $booking->hotel_id,
            'amount' => $amountInKobo,
            'amount_paid' => 0,
            'payment_method' => 'card',
            'payment_reference' => $paymentReference,
            'type' => 'full_payment',
            'status' => 'pending',
            'expires_at' => now()->addHours(2),
        ]);

        $callbackUrl = route('public.booking.callback', [
            'slug' => $booking->hotel->slug,
            'reference' => $paymentReference
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->flutterwaveSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.flutterwave.com/v3/payments', [
            'tx_ref' => $paymentReference,
            'amount' => $amountInKobo / 100,
            'currency' => 'NGN',
            'redirect_url' => $callbackUrl,
            'payment_options' => 'card, banktransfer, ussd',
            'customer' => [
                'email' => $booking->guest->email ?? $booking->hotel->email,
                'name' => $booking->guest->name,
            ],
            'customizations' => [
                'title' => $booking->hotel->name . ' - Booking Payment',
                'description' => 'Booking Reference: ' . $booking->booking_reference,
            ],
            'meta' => [
                'booking_reference' => $booking->booking_reference,
                'hotel_id' => $booking->hotel_id,
                'payment_type' => 'full_payment',
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Flutterwave: ' . $response->json('message'));
        }

        $data = $response->json('data');

        return [
            'checkout_url' => $data['link'],
            'payment_reference' => $paymentReference,
        ];
    }

    /**
     * Initiate Paystack payment (fallback)
     */
    protected function initiatePaystackPayment(Booking $booking, int $amountInKobo): array
    {
        $paymentReference = 'AFS-PAY-' . Str::upper(Str::random(10));

        Payment::create([
            'booking_id' => $booking->id,
            'hotel_id' => $booking->hotel_id,
            'amount' => $amountInKobo,
            'amount_paid' => 0,
            'payment_method' => 'card',
            'payment_reference' => $paymentReference,
            'type' => 'full_payment',
            'status' => 'pending',
            'expires_at' => now()->addHours(2),
        ]);

        $callbackUrl = route('public.booking.callback', [
            'slug' => $booking->hotel->slug,
            'reference' => $paymentReference
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $booking->guest->email ?? $booking->hotel->email,
            'amount' => $amountInKobo,
            'reference' => $paymentReference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'booking_reference' => $booking->booking_reference,
                'hotel_id' => $booking->hotel_id,
                'payment_type' => 'full_payment',
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Paystack: ' . $response->json('message'));
        }

        $data = $response->json('data');

        return [
            'checkout_url' => $data['authorization_url'],
            'payment_reference' => $paymentReference,
        ];
    }

    /**
     * Initiate deposit checkout (kept for backward compatibility if needed)
     */
    public function initiateDepositCheckout(Booking $booking, int $amountInKobo): array
    {
        return $this->initiateFullPaymentCheckout($booking, $amountInKobo);
    }
}