<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $html = $this->emailBase(
            "Reset your AfricStay password",
            "
            <h2 style='color:#0a3622;font-weight:600;margin:0 0 12px;font-size:20px;'>🔐 Reset your password</h2>
            
            <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Hello there,</p>
            
            <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>We received a request to reset your AfricStay password. Click below to choose a new one:</p>
            
            <div style='text-align:center;margin:28px 0 24px;'>
                <a href='{$resetUrl}' style='display:inline-block;padding:14px 40px;background:#0a3622;color:#ffffff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:600;letter-spacing:0.3px;border:1px solid #0a3622;'>Reset password</a>
            </div>
            
            <p style='font-size:13px;color:#6a8a6a;margin:0 0 6px;'>If the button doesn't work, copy this link:</p>
            <p style='font-size:13px;word-break:break-all;background:#f8faf8;padding:12px 16px;border-radius:6px;color:#0a3622;border:1px solid #e8efe8;margin:0 0 20px;'>{{$resetUrl}}</p>
            
            <hr style='border:none;border-top:1px solid #e8efe8;margin:24px 0;'>
            
            <p style='font-size:13px;color:#6a8a6a;margin:0 0 4px;'>This link expires in <strong style='color:#0a3622;'>60 minutes</strong>.</p>
            <p style='font-size:13px;color:#6a8a6a;margin:0;'>Didn't request this? Ignore this email — your account is safe.</p>
            "
        );

        return $this->send(
            $toEmail,
            'Reset your AfricStay password',
            str_replace('{{$resetUrl}}', $resetUrl, $html)
        );
    }

    public function sendStaffInvite(string $toEmail, string $hotelName, string $inviteUrl): bool
    {
        $html = $this->emailBase(
            "You've been invited to join {$hotelName} on AfricStay",
            "
            <h2 style='color:#0a3622;font-weight:600;margin:0 0 12px;font-size:20px;'>🎉 You're invited!</h2>
            
            <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Hello there,</p>
            
            <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>You've been invited to join <strong style='color:#0a3622;'>{$hotelName}</strong> as a staff member on AfricStay.</p>
            
            <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f8faf8;border-radius:8px;overflow:hidden;'>
                <tr style='border-bottom:1px solid #e8efe8;'>
                    <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Hotel</td>
                    <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$hotelName}</td>
                </tr>
                <tr>
                    <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Role</td>
                    <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>Staff member</td>
                </tr>
            </table>
            
            <p style='margin:0 0 20px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Click below to set your password and activate your account:</p>
            
            <div style='text-align:center;margin:28px 0 24px;'>
                <a href='{$inviteUrl}' style='display:inline-block;padding:14px 40px;background:#0a3622;color:#ffffff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:600;letter-spacing:0.3px;border:1px solid #0a3622;'>Activate account</a>
            </div>
            
            <p style='font-size:13px;color:#6a8a6a;margin:0 0 6px;'>If the button doesn't work, copy this link:</p>
            <p style='font-size:13px;word-break:break-all;background:#f8faf8;padding:12px 16px;border-radius:6px;color:#0a3622;border:1px solid #e8efe8;margin:0 0 20px;'>{{$inviteUrl}}</p>
            
            <hr style='border:none;border-top:1px solid #e8efe8;margin:24px 0;'>
            
            <p style='font-size:13px;color:#6a8a6a;margin:0 0 4px;'>This invitation expires in <strong style='color:#0a3622;'>72 hours</strong>.</p>
            <p style='font-size:13px;color:#6a8a6a;margin:0;'>Questions? <a href='mailto:support@africstayhms.com' style='color:#0a3622;text-decoration:underline;'>support@africstayhms.com</a></p>
            "
        );

        return $this->send(
            $toEmail,
            "You've been invited to join {$hotelName} on AfricStay",
            str_replace('{{$inviteUrl}}', $inviteUrl, $html)
        );
    }

    protected function emailBase(string $preheader, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AfricStay</title>
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
        <h1>AfricStay</h1>
        <p>Hotel management, simplified</p>
    </div>
    <div class="body">
        {$body}
    </div>
    <div class="footer">
        <p>AfricStay — All rights reserved.</p>
        <p><a href="mailto:support@africstayhms.com">support@africstayhms.com</a></p>
    </div>
</div>
</body>
</html>
HTML;
    }
}