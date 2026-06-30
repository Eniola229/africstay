<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Owner-initiated withdrawal to their bank account, via Flutterwave's
 * Transfer API only (no Paystack fallback — a failed transfer is reported
 * to the user as failed, not silently retried on a different provider).
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
            $hotel->increment('wallet_balance', $amountKobo); // revert
            $withdrawal->update([
                'status' => 'failed',
                // tryFlutterwaveTransfer() already wrote the real reason onto the
                // withdrawal when it failed — fall back to a generic message only
                // if for some reason it didn't get set (e.g. an unexpected exception).
                'failure_reason' => $withdrawal->fresh()->failure_reason ?: 'Flutterwave rejected the transfer.',
            ]);
            return $withdrawal->fresh();
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
            $withdrawal->update(['failure_reason' => 'Flutterwave is not configured (missing secret key).']);
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

            $message = $response->json('message') ?? 'Flutterwave rejected the transfer.';

            Log::warning('Flutterwave transfer failed.', [
                'withdrawal' => $withdrawal->id,
                'http_status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $withdrawal->update(['failure_reason' => $message]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Flutterwave transfer exception: '.$e->getMessage(), ['withdrawal' => $withdrawal->id]);
            $withdrawal->update(['failure_reason' => 'Flutterwave transfer request failed: '.$e->getMessage()]);
            return null;
        }
    }

    /**
     * NOT currently called — Flutterwave-only per current requirements.
     * Left in place in case provider fallback is reinstated later.
     */

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

    public function confirmCompleted(string $reference, string $providerRef): void
    {
        $withdrawal = Withdrawal::where('reference', $reference)->first();
        if (! $withdrawal || $withdrawal->status !== 'processing') {
            return; // already handled, or not ours
        }
        $this->markCompleted($withdrawal);
    }

    public function confirmFailed(string $reference, string $reason): void
    {
        $withdrawal = Withdrawal::where('reference', $reference)->first();
        if (! $withdrawal || $withdrawal->status !== 'processing') {
            return;
        }
        $withdrawal->hotel->increment('wallet_balance', $withdrawal->amount); // revert
        $withdrawal->update(['status' => 'failed', 'failure_reason' => $reason]);
    }

    public function markCompleted(Withdrawal $withdrawal): void
    {
        $withdrawal->update(['status' => 'completed', 'processed_at' => now()]);

        $hotel = $withdrawal->fresh()->hotel;
        $owner = $hotel->owner;
        $fallbackMsg = "Your withdrawal of ₦".number_format($withdrawal->amountNaira(), 2)." to {$withdrawal->account_number} ({$withdrawal->bank_name}) is complete.";

        $this->notify->notifyHotel($hotel, 'withdrawal_completed', $fallbackMsg,
            $owner?->email ? 'Your withdrawal is complete' : null,
            $owner?->email ? $this->emailWithdrawalCompleted($hotel, $withdrawal) : null
        );
    }

    // ─── HOTEL BRANDED EMAIL TEMPLATE ───────────────────────────────────────────
    // Mirrors BookingController's emailBase()/table styling so every guest- and
    // owner-facing email looks consistent across the platform.

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

    protected function emailWithdrawalCompleted(Hotel $hotel, Withdrawal $withdrawal): string
    {
        $body = "
        <h2>✅ Withdrawal completed</h2>

        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Hi there,</p>

        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Your withdrawal from <strong style='color:#0a3622;'>{$hotel->name}</strong>'s wallet has been processed successfully. Here are the details:</p>

        <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f8faf8;border-radius:8px;overflow:hidden;'>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Reference</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:600;'>{$withdrawal->reference}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Amount</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:600;'>₦".number_format($withdrawal->amountNaira(), 2)."</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Bank</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$withdrawal->bank_name}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Account number</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$withdrawal->account_number}</td>
            </tr>
            <tr style='border-bottom:1px solid #e8efe8;'>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Account name</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$withdrawal->account_name}</td>
            </tr>
            <tr>
                <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Processed at</td>
                <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>".$withdrawal->processed_at->format('d M Y h:i A')."</td>
            </tr>
        </table>

        <hr class='divider'>

        <p style='font-size:13px;color:#6a8a6a;margin:0;'>It may take a short while to reflect in your bank account depending on your bank's processing time.</p>
        <p style='font-size:13px;color:#6a8a6a;margin:4px 0 0;'>Questions? <a href='mailto:support@africstayhms.com' style='color:#0a3622;text-decoration:underline;'>support@africstayhms.com</a></p>";

        return $this->emailBase("Your withdrawal of ₦".number_format($withdrawal->amountNaira(), 2)." is complete", $body, $hotel->name);
    }
}