<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Termii SMS wrapper. Queue the actual send (see SendSmsJob, Phase 4) —
 * for Phase 1 auth flows (OTP, invite links) we call ->send() directly
 * since these are low-volume, user-blocking actions.
 */
class SmsService
{
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiKey = config('services.termii.key', '');
        $this->senderId = config('services.termii.sender_id', 'AfricStay');
    }

    public function send(string $phoneE164, string $message): bool
    {
        if (blank($this->apiKey)) {
            Log::warning('Termii not configured — SMS not sent.', ['to' => $phoneE164]);
            return false;
        }

        try {
            $response = Http::post('https://api.ng.termii.com/api/sms/send', [
                'to' => $phoneE164,
                'from' => $this->senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
                'api_key' => $this->apiKey,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Termii send failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendOtp(string $phoneE164): string
    {
        $otp = (string) random_int(100000, 999999);
        $this->send($phoneE164, "Your AfricStay verification code is {$otp}. It expires in 10 minutes.");
        return $otp;
    }
}