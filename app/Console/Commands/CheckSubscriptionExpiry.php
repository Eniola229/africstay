<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry';
    protected $description = 'Send renewal reminders and flip expired subscriptions to past_due/expired.';

    public function __construct(
        protected SmsService $sms,
        protected EmailService $email,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->sendReminders(7, 'renewal_reminder_7d_sent');
        $this->sendReminders(3, 'renewal_reminder_3d_sent');
        $this->sendReminders(1, 'renewal_reminder_1d_sent');
        $this->sweepExpired();

        return self::SUCCESS;
    }

    protected function sendReminders(int $daysOut, string $flagColumn): void
    {
        $windowStart = now()->addDays($daysOut)->startOfDay();
        $windowEnd = now()->addDays($daysOut)->endOfDay();

        $subscriptions = Subscription::where('is_active', true)
            ->where('status', 'active')
            ->where($flagColumn, false)
            ->whereBetween('ends_at', [$windowStart, $windowEnd])
            ->with('hotel.owner')
            ->get();

        foreach ($subscriptions as $subscription) {
            $hotel = $subscription->hotel;
            $owner = $hotel?->owner;

            if (! $hotel) {
                continue;
            }

            $dateLabel = $subscription->ends_at->format('jS M Y');
            $planName = ucfirst($subscription->tier);
            $message = "Hi {$hotel->name}, your AfricStay {$subscription->tier} subscription expires on {$dateLabel} ({$daysOut} day(s) away). Renew now to avoid losing access.";

            $this->sms->send($hotel->phone, $message);

            $recipientEmail = $owner?->email ?? $hotel->email;
            if ($recipientEmail) {
                $html = $this->emailBase(
                    "Your AfricStay subscription expires in {$daysOut} day(s)",
                    "
                    <h2 style='color:#0a3622;font-weight:600;margin:0 0 12px;font-size:20px;'>⏰ Time to renew your subscription</h2>
                    
                    <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Hi <strong style='color:#0a3622;'>{$hotel->name}</strong> team,</p>
                    
                    <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Your AfricStay <strong>{$planName}</strong> plan expires in <strong style='color:#0a3622;'>{$daysOut} day(s)</strong> on <strong>{$dateLabel}</strong>.</p>
                    
                    <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f8faf8;border-radius:8px;overflow:hidden;'>
                        <tr style='border-bottom:1px solid #e8efe8;'>
                            <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;width:45%;'>Plan</td>
                            <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$planName}</td>
                        </tr>
                        <tr style='border-bottom:1px solid #e8efe8;'>
                            <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Expiry date</td>
                            <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$dateLabel}</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;font-size:14px;color:#4a6a4a;font-weight:600;'>Days remaining</td>
                            <td style='padding:12px 16px;font-size:14px;color:#0a3622;font-weight:500;'>{$daysOut}</td>
                        </tr>
                    </table>
                    
                    <p style='margin:0 0 20px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Renew now to keep your dashboard running smoothly.</p>
                    
                    <div style='text-align:center;margin:28px 0 20px;'>
                        <a href='" . route('hotel.subscription.plans') . "' style='display:inline-block;padding:14px 40px;background:#0a3622;color:#ffffff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:600;letter-spacing:0.3px;border:1px solid #0a3622;'>Renew subscription</a>
                    </div>
                    
                    <hr style='border:none;border-top:1px solid #e8efe8;margin:24px 0;'>
                    
                    <p style='font-size:13px;color:#6a8a6a;margin:0 0 4px;'>Already renewed? Ignore this reminder.</p>
                    <p style='font-size:13px;color:#6a8a6a;margin:0;'>Questions? <a href='mailto:support@africstayhms.com' style='color:#0a3622;text-decoration:underline;'>support@africstayhms.com</a></p>
                    "
                );

                $this->email->send(
                    $recipientEmail,
                    "Your AfricStay subscription expires in {$daysOut} day(s)",
                    $html
                );
            }

            $subscription->update([$flagColumn => true]);
            Log::info("Sent {$daysOut}-day renewal reminder for hotel {$hotel->id}.");
        }
    }

    protected function sweepExpired(): void
    {
        $expiredSubscriptions = Subscription::where('is_active', true)
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->with('hotel')
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $hotel = $subscription->hotel;
            if (! $hotel) {
                continue;
            }

            $daysSinceExpiry = now()->diffInDays($subscription->ends_at);
            $graceDays = \App\Models\Hotel::GRACE_PERIOD_DAYS;

            if ($daysSinceExpiry <= $graceDays) {
                $subscription->update(['status' => 'past_due']);
                $hotel->update(['subscription_status' => 'past_due']);

                $owner = $hotel->owner;
                $msg = "Your AfricStay subscription expired {$daysSinceExpiry} day(s) ago. You're within your {$graceDays}-day grace period. Renew now to keep full access.";
                
                $this->sms->send($hotel->phone, $msg);
                
                $recipientEmail = $owner?->email ?? $hotel->email;
                if ($recipientEmail) {
                    $html = $this->emailBase(
                        "Your subscription is in grace period",
                        "
                        <h2 style='color:#8a6d0a;font-weight:600;margin:0 0 12px;font-size:20px;'>⚠️ Grace period active</h2>
                        
                        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Hi <strong style='color:#0a3622;'>{$hotel->name}</strong> team,</p>
                        
                        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Your subscription expired <strong>{$daysSinceExpiry} day(s)</strong> ago. You're within your <strong>{$graceDays}-day grace period</strong> — your account is still accessible.</p>
                        
                        <div style='background:#fdf6e3;border-radius:8px;padding:16px 20px;margin:20px 0;border-left:4px solid #d4a017;'>
                            <p style='margin:0;font-size:14px;color:#6a5a0a;line-height:1.6;'>
                                <strong>⏳ Action required:</strong> Renew now to keep full access.
                            </p>
                        </div>
                        
                        <div style='text-align:center;margin:28px 0 20px;'>
                            <a href='" . route('hotel.subscription.plans') . "' style='display:inline-block;padding:14px 40px;background:#d4a017;color:#ffffff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:600;letter-spacing:0.3px;border:1px solid #d4a017;'>Renew now</a>
                        </div>
                        
                        <hr style='border:none;border-top:1px solid #e8efe8;margin:24px 0;'>
                        
                        <p style='font-size:13px;color:#6a8a6a;margin:0 0 4px;'>If you don't renew, your account will be locked.</p>
                        <p style='font-size:13px;color:#6a8a6a;margin:0;'>Help? <a href='mailto:support@africstayhms.com' style='color:#0a3622;text-decoration:underline;'>support@africstayhms.com</a></p>
                        "
                    );
                    
                    $this->email->send(
                        $recipientEmail,
                        'Your AfricStay subscription is in grace period',
                        $html
                    );
                }

                Log::info("Subscription for hotel {$hotel->id} moved to past_due.");

            } else {
                $subscription->update(['status' => 'expired', 'is_active' => false]);
                $hotel->update(['subscription_status' => 'expired']);

                $owner = $hotel->owner;
                $msg = "Your AfricStay subscription has expired and access is now locked. Renew to continue using your hotel dashboard.";
                
                $this->sms->send($hotel->phone, $msg);
                
                $recipientEmail = $owner?->email ?? $hotel->email;
                if ($recipientEmail) {
                    $html = $this->emailBase(
                        "Your AfricStay subscription has expired",
                        "
                        <h2 style='color:#8a1a1a;font-weight:600;margin:0 0 12px;font-size:20px;'>❌ Subscription expired</h2>
                        
                        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Hi <strong style='color:#0a3622;'>{$hotel->name}</strong> team,</p>
                        
                        <p style='margin:0 0 16px;font-size:15px;line-height:1.7;color:#2d2d2d;'>Your subscription expired <strong>{$daysSinceExpiry} day(s)</strong> ago and your grace period has ended. Your dashboard access has been <strong style='color:#8a1a1a;'>locked</strong>.</p>
                        
                        <div style='background:#fde8e8;border-radius:8px;padding:16px 20px;margin:20px 0;border-left:4px solid #c0392b;'>
                            <p style='margin:0;font-size:14px;color:#6a1a1a;line-height:1.6;'>
                                <strong>🔒 Access locked:</strong> You can no longer manage bookings or use AfricStay features until you renew.
                            </p>
                        </div>
                        
                        <div style='text-align:center;margin:28px 0 20px;'>
                            <a href='" . route('hotel.subscription.plans') . "' style='display:inline-block;padding:14px 40px;background:#c0392b;color:#ffffff;text-decoration:none;border-radius:6px;font-size:15px;font-weight:600;letter-spacing:0.3px;border:1px solid #c0392b;'>Restore access</a>
                        </div>
                        
                        <hr style='border:none;border-top:1px solid #e8efe8;margin:24px 0;'>
                        
                        <p style='font-size:13px;color:#6a8a6a;margin:0 0 4px;'>Renew now to restore full access.</p>
                        <p style='font-size:13px;color:#6a8a6a;margin:0;'>Questions? <a href='mailto:support@africstayhms.com' style='color:#0a3622;text-decoration:underline;'>support@africstayhms.com</a></p>
                        "
                    );
                    
                    $this->email->send(
                        $recipientEmail,
                        'Your AfricStay subscription has expired',
                        $html
                    );
                }

                Log::info("Subscription for hotel {$hotel->id} expired and locked.");
            }
        }
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