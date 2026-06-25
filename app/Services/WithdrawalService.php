<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Owner-initiated withdrawal to their bank account. Flutterwave Transfer
 * API first, Paystack Transfer API as fallback — same provider-fallback
 * rule as everywhere else in the spec.
 *
 * Money is deducted from the wallet immediately on success; reverted if the
 * transfer call itself fails outright. A transfer that's accepted but later
 * fails on the provider's side would be reconciled via their transfer
 * webhook in a fuller build — flagged in code below for Phase 2.1.
 */
class WithdrawalService
{
    public function __construct(protected NotificationFallbackService $notify) {}

    public function initiate(Hotel $hotel, int $amountKobo, string $bankName, string $bankCode, string $accountNumber, string $accountName, string $initiatedByUserId): Withdrawal
    {
        if ($amountKobo < Hotel::MIN_WITHDRAWAL_KOBO) {
            throw new \InvalidArgumentException('Minimum withdrawal amount is ₦10,000.');
        }

        if ($hotel->wallet_balance < $amountKobo) {
            throw new \InvalidArgumentException('Insufficient wallet balance.');
        }

        $reference = 'AFS-WD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        $withdrawal = Withdrawal::create([
            'hotel_id' => $hotel->id,
            'amount' => $amountKobo,
            'bank_name' => $bankName,
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'reference' => $reference,
            'provider' => 'flutterwave',
            'status' => 'pending',
            'initiated_by' => $initiatedByUserId,
        ]);

        // Deduct up front so the hotel can't request the same balance twice
        // while a transfer is in flight; reverted below on outright failure.
        $hotel->debitWallet($amountKobo);

        $result = $this->tryFlutterwaveTransfer($withdrawal);

        if (! $result) {
            $withdrawal->update(['provider' => 'paystack']);
            $result = $this->tryPaystackTransfer($withdrawal);
        }

        if (! $result) {
            $hotel->increment('wallet_balance', $amountKobo); // revert
            $withdrawal->update(['status' => 'failed', 'failure_reason' => 'Both payment providers rejected the transfer.']);
            return $withdrawal;
        }

        $withdrawal->update([
            'status' => 'processing',
            'provider_reference' => $result['reference'] ?? null,
        ]);

        return $withdrawal;
    }

    protected function tryFlutterwaveTransfer(Withdrawal $withdrawal): ?array
    {
        $key = config('services.flutterwave.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            $response = Http::withToken($key)->post('https://api.flutterwave.com/v3/transfers', [
                'account_bank' => $withdrawal->bank_code,
                'account_number' => $withdrawal->account_number,
                'amount' => $withdrawal->amount / 100,
                'currency' => 'NGN',
                'reference' => $withdrawal->reference,
                'narration' => 'AfricStay withdrawal',
            ]);

            if ($response->successful() && in_array($response->json('status'), ['success', 'pending'])) {
                return ['reference' => (string) $response->json('data.id')];
            }

            Log::warning('Flutterwave transfer failed — falling back to Paystack.', ['withdrawal' => $withdrawal->id]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Flutterwave transfer exception: '.$e->getMessage());
            return null;
        }
    }

    protected function tryPaystackTransfer(Withdrawal $withdrawal): ?array
    {
        $key = config('services.paystack.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            $recipientResponse = Http::withToken($key)->post('https://api.paystack.co/transferrecipient', [
                'type' => 'nuban',
                'name' => $withdrawal->account_name,
                'account_number' => $withdrawal->account_number,
                'bank_code' => $withdrawal->bank_code,
                'currency' => 'NGN',
            ]);

            if (! $recipientResponse->successful()) {
                return null;
            }

            $recipientCode = $recipientResponse->json('data.recipient_code');

            $response = Http::withToken($key)->post('https://api.paystack.co/transfer', [
                'source' => 'balance',
                'amount' => $withdrawal->amount,
                'recipient' => $recipientCode,
                'reference' => $withdrawal->reference,
                'reason' => 'AfricStay withdrawal',
            ]);

            if ($response->successful() && $response->json('status') === true) {
                return ['reference' => (string) $response->json('data.id')];
            }

            Log::error('Paystack transfer also failed.', ['withdrawal' => $withdrawal->id]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Paystack transfer exception: '.$e->getMessage());
            return null;
        }
    }

    public function markCompleted(Withdrawal $withdrawal): void
    {
        $withdrawal->update(['status' => 'completed', 'processed_at' => now()]);

        $hotel = $withdrawal->hotel;
        $owner = $hotel->owner;
        $msg = "Your withdrawal of ₦".number_format($withdrawal->amountNaira(), 2)." to {$withdrawal->account_number} ({$withdrawal->bank_name}) is complete.";

        $this->notify->notifyHotel($hotel, 'withdrawal_completed', $msg,
            $owner?->email ? 'Withdrawal completed' : null,
            $owner?->email ? "<p>{$msg}</p>" : null
        );
    }
}
