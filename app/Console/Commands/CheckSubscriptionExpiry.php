<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Run daily (see routes/console.php). Two jobs in one pass:
 *
 *  1. REMINDERS — for active subscriptions ending in 7 / 3 / 1 day(s), send an
 *     email (Brevo) + SMS (Termii) to the hotel, once per threshold (tracked via
 *     the renewal_reminder_*_sent flags so we never spam the same hotel twice).
 *
 *  2. EXPIRY SWEEP — for active subscriptions whose ends_at has already passed:
 *       - within Hotel::GRACE_PERIOD_DAYS  -> hotel.subscription_status = past_due (still usable, warning shown)
 *       - past the grace period            -> hotel.subscription_status = expired (locked out)
 */
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
            $message = "Hi {$hotel->name}, your AfricStay {$subscription->tier} subscription expires on {$dateLabel} ({$daysOut} day(s) away). Renew now to avoid losing access.";

            // SMS always goes to the hotel's phone — it's always required (spec note #4).
            $this->sms->send($hotel->phone, $message);

            // Email to owner if they have one, else hotel's own email if set.
            $recipientEmail = $owner?->email ?? $hotel->email;
            if ($recipientEmail) {
                $this->email->send(
                    $recipientEmail,
                    "Your AfricStay subscription expires in {$daysOut} day(s)",
                    "<p>{$message}</p><p><a href=\"".route('hotel.subscription.plans')."\">Renew your subscription</a></p>"
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
            } else {
                $subscription->update(['status' => 'expired', 'is_active' => false]);
                $hotel->update(['subscription_status' => 'expired']);

                $owner = $hotel->owner;
                $msg = "Your AfricStay subscription has expired and access is now locked. Renew to continue using your hotel dashboard.";
                $this->sms->send($hotel->phone, $msg);
                if ($owner?->email ?? $hotel->email) {
                    $this->email->send($owner?->email ?? $hotel->email, 'Your AfricStay subscription has expired', "<p>{$msg}</p>");
                }
            }
        }
    }
}
