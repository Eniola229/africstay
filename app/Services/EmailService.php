<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Brevo (formerly Sendinblue) transactional email wrapper.
 * Uses the HTTP API directly (not SMTP) per spec.
 */
class EmailService
{
    protected string $apiKey;
    protected string $fromEmail;
    protected string $fromName;

    public function __construct()
    {
        $this->apiKey = config('services.brevo.key', '');
        $this->fromEmail = config('services.brevo.from_email', 'no-reply@africstayhms.com');
        $this->fromName = config('services.brevo.from_name', 'AfricStay');
    }

    public function send(string $toEmail, string $subject, string $htmlContent): bool
    {
        if (blank($this->apiKey)) {
            Log::warning('Brevo not configured — email not sent.', ['to' => $toEmail]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['name' => $this->fromName, 'email' => $this->fromEmail],
                'to' => [['email' => $toEmail]],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Brevo send failed: '.$e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetLink(string $toEmail, string $resetUrl): bool
    {
        return $this->send(
            $toEmail,
            'Reset your AfricStay password',
            "<p>Click below to reset your password. This link expires in 60 minutes.</p>
             <p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>"
        );
    }

    public function sendStaffInvite(string $toEmail, string $hotelName, string $inviteUrl): bool
    {
        return $this->send(
            $toEmail,
            "You've been invited to join {$hotelName} on AfricStay",
            "<p>You've been added as staff for <strong>{$hotelName}</strong>.</p>
             <p>Click below to set your password and log in:</p>
             <p><a href=\"{$inviteUrl}\">{$inviteUrl}</a></p>"
        );
    }
}