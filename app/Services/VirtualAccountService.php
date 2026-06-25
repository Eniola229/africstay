<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generates a unique virtual/dedicated account for a guest at check-in.
 * Flutterwave first; if that call fails for ANY reason, automatically
 * retries with Paystack — payment provider fallback is not optional (spec).
 *
 * Returns a `payments` row (status: pending) holding the account details
 * the receptionist shows the guest. The booking is only credited once the
 * matching webhook confirms a transfer into that account.
 */
class VirtualAccountService
{
    public function generate(Booking $booking): Payment
    {
        $reference = 'AFS-PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        $accountName = "AfricStay/{$booking->room->room_number}";

        $account = $this->tryFlutterwave($booking, $reference, $accountName);
        $provider = 'flutterwave';

        if (! $account) {
            $account = $this->tryPaystack($booking, $reference, $accountName);
            $provider = 'paystack';
        }

        if (! $account) {
            Log::error('Virtual account generation failed on both providers.', ['booking' => $booking->id]);
            $account = [
                'account_number' => 'PENDING',
                'bank_name' => 'Unavailable — contact support',
            ];
        }

        return Payment::create([
            'booking_id' => $booking->id,
            'hotel_id' => $booking->hotel_id,
            'amount' => $booking->balance > 0 ? $booking->balance : $booking->total_amount,
            'payment_method' => 'virtual_account',
            'provider' => $provider,
            'virtual_account_number' => $account['account_number'],
            'virtual_account_bank' => $account['bank_name'],
            'virtual_account_name' => $accountName,
            'payment_reference' => $reference,
            'status' => 'pending',
        ]);
    }

    protected function tryFlutterwave(Booking $booking, string $reference, string $accountName): ?array
    {
        $key = config('services.flutterwave.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            $response = Http::withToken($key)->post('https://api.flutterwave.com/v3/virtual-account-numbers', [
                'email' => $booking->guest->email ?? $booking->hotel->email ?? 'guest@africstayhms.com',
                'tx_ref' => $reference,
                'is_permanent' => false,
                'amount' => $booking->balance > 0 ? $booking->balance / 100 : null,
                'narration' => $accountName,
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return [
                    'account_number' => $response->json('data.account_number'),
                    'bank_name' => $response->json('data.bank_name', 'Flutterwave'),
                ];
            }

            Log::warning('Flutterwave virtual account creation failed — falling back to Paystack.', [
                'booking' => $booking->id, 'response' => $response->json(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Flutterwave virtual account exception: '.$e->getMessage());
            return null;
        }
    }

    protected function tryPaystack(Booking $booking, string $reference, string $accountName): ?array
    {
        $key = config('services.paystack.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            // Paystack dedicated virtual accounts require a customer to exist first.
            $customerResponse = Http::withToken($key)->post('https://api.paystack.co/customer', [
                'email' => $booking->guest->email ?? $booking->hotel->email ?? 'guest@africstayhms.com',
                'first_name' => $booking->guest->name,
                'phone' => $booking->guest->phone ?? $booking->hotel->phone,
            ]);

            if (! $customerResponse->successful()) {
                Log::error('Paystack customer creation failed.', ['booking' => $booking->id]);
                return null;
            }

            $customerCode = $customerResponse->json('data.customer_code');

            $response = Http::withToken($key)->post('https://api.paystack.co/dedicated_account', [
                'customer' => $customerCode,
                'preferred_bank' => 'wema-bank',
            ]);

            if ($response->successful() && $response->json('status') === true) {
                return [
                    'account_number' => $response->json('data.account_number'),
                    'bank_name' => $response->json('data.bank.name', 'Paystack'),
                ];
            }

            Log::error('Paystack dedicated account creation also failed.', ['booking' => $booking->id, 'response' => $response->json()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Paystack virtual account exception: '.$e->getMessage());
            return null;
        }
    }
}
