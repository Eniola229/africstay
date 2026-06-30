<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles subscription checkout initiation (Flutterwave first, Paystack fallback)
 * and activation once a webhook confirms payment.
 *
 * Flow:
 *  1. initiateCheckout() creates a `pending` Subscription + SubscriptionPayment,
 *     then asks Flutterwave for a hosted checkout link. If that call fails for
 *     any reason, it automatically retries with Paystack instead (spec: payment
 *     provider fallback is not optional).
 *  2. The hotel owner is redirected to that link to pay.
 *  3. The provider's webhook hits FlutterwaveWebhookController / PaystackWebhookController,
 *     which call confirmPayment() — idempotent on payment_reference.
 * 
 * Renewal/Upgrade logic:
 *  - If user renews or upgrades an active subscription, the new period starts from
 *    when the current subscription ends (preserving unused days + adding new period).
 *  - If the current subscription is already expired or nonexistent, starts from now().
 */
class SubscriptionBillingService
{
    public function initiateCheckout(Hotel $hotel, string $tier, string $billingCycle): array
    {
        $amount = Subscription::amountFor($tier, $billingCycle);

        if ($amount === null) {
            throw new \InvalidArgumentException('Enterprise tier has no self-serve checkout — route to sales instead.');
        }

        $subscription = Subscription::create([
            'hotel_id' => $hotel->id,
            'tier' => $tier,
            'billing_cycle' => $billingCycle,
            'base_monthly_fee' => Hotel::TIER_MONTHLY_FEES[$tier],
            'discount_percent' => $billingCycle === 'yearly' ? Hotel::YEARLY_DISCOUNT_PERCENT : 0,
            'amount_due' => $amount,
            'status' => 'pending',
            'is_active' => false,
        ]);

        $reference = 'AFS-SUB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        $payment = SubscriptionPayment::create([
            'hotel_id' => $hotel->id,
            'subscription_id' => $subscription->id,
            'tier' => $tier,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'provider' => 'flutterwave',
            'payment_reference' => $reference,
            'status' => 'pending',
        ]);

        $checkoutUrl = $this->tryFlutterwave($hotel, $payment, $amount, $reference);

        if (! $checkoutUrl) {
            $payment->update(['provider' => 'paystack']);
            $checkoutUrl = $this->tryPaystack($hotel, $payment, $amount, $reference);
        }

        if (! $checkoutUrl) {
            $payment->update(['status' => 'failed']);
            throw new \RuntimeException('Could not start payment with either provider. Please try again shortly.');
        }

        return ['checkout_url' => $checkoutUrl, 'reference' => $reference, 'subscription' => $subscription];
    }

    protected function tryFlutterwave(Hotel $hotel, SubscriptionPayment $payment, int $amountKobo, string $reference): ?string
    {
        $key = config('services.flutterwave.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            $response = Http::withToken($key)->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $reference,
                'amount' => $amountKobo / 100, // Flutterwave expects naira, not kobo
                'currency' => 'NGN',
                'redirect_url' => route('hotel.subscription.callback'),
                'customer' => [
                    'email' => $hotel->email ?? $hotel->owner?->email ?? 'billing@africstayhms.com',
                    'phonenumber' => $hotel->phone,
                    'name' => $hotel->name,
                ],
                'customizations' => [
                    'title' => 'AfricStay Subscription',
                    'description' => "{$payment->tier} plan — {$payment->billing_cycle} billing",
                ],
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json('data.link');
            }

            Log::warning('Flutterwave subscription checkout init failed, falling back to Paystack.', [
                'reference' => $reference, 'response' => $response->json(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Flutterwave init exception: '.$e->getMessage());
            return null;
        }
    }

    protected function tryPaystack(Hotel $hotel, SubscriptionPayment $payment, int $amountKobo, string $reference): ?string
    {
        $key = config('services.paystack.secret_key');
        if (blank($key)) {
            return null;
        }

        try {
            $response = Http::withToken($key)->post('https://api.paystack.co/transaction/initialize', [
                'email' => $hotel->email ?? $hotel->owner?->email ?? 'billing@africstayhms.com',
                'amount' => $amountKobo, // Paystack expects kobo — matches our storage unit directly
                'reference' => $reference,
                'callback_url' => route('hotel.subscription.callback'),
            ]);

            if ($response->successful() && $response->json('status') === true) {
                return $response->json('data.authorization_url');
            }

            Log::error('Paystack subscription checkout init also failed.', [
                'reference' => $reference, 'response' => $response->json(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Paystack init exception: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Called by both webhook controllers once payment is confirmed.
     * Idempotent: if this reference was already confirmed, does nothing further.
     * 
     * For renewals/upgrades: if the hotel has an active subscription that hasn't expired yet,
     * the new subscription period extends from that end date. Otherwise, it starts from now().
     */
    public function confirmPayment(string $paymentReference, string $providerReference): void
    {
        $payment = SubscriptionPayment::where('payment_reference', $paymentReference)->first();

        if (! $payment || $payment->status === 'confirmed') {
            return; // unknown reference, or already processed — never double-activate
        }

        $payment->update([
            'status' => 'confirmed',
            'provider_reference' => $providerReference,
            'paid_at' => now(),
        ]);

        $subscription = $payment->subscription;
        $hotel = $payment->hotel;

        // Determine the start date for the new subscription.
        // If there's an active subscription that hasn't expired, extend from its end date.
        // Otherwise, start from now.
        $currentActiveSubscription = $hotel->subscriptions()
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->first();

        if ($currentActiveSubscription) {
            $startsAt = $currentActiveSubscription->ends_at;
        } else {
            $startsAt = now();
        }

        $endsAt = $payment->billing_cycle === 'yearly' 
            ? $startsAt->copy()->addYear() 
            : $startsAt->copy()->addMonth();

        // Deactivate any previously-active subscription for this hotel before activating the new one.
        $hotel->subscriptions()->where('is_active', true)->update(['is_active' => false]);

        $subscription->update([
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
        ]);

        $hotel->update([
            'tier' => $payment->tier,
            'subscription_status' => 'active',
            'subscription_ends_at' => $endsAt,
        ]);
    }
}