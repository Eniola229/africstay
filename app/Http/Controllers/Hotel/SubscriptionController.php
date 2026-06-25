<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Subscription;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Subscription payment screens — used both:
 *  - during onboarding step 2 (first-ever payment, gates access to the dashboard), and
 *  - later for renewals (when a subscription is past_due/expired).
 */
class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionBillingService $billing) {}

    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    /** Plan + billing-cycle picker with live pricing (20% off shown for yearly). */
    public function showPlans()
    {
        return view('hotel.subscription.plans', [
            'hotel' => $this->hotel(),
            'tiers' => $this->tierData(),
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'tier' => ['required', 'in:starter,growth,pro'], // enterprise excluded — sales-assisted only
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        try {
            $result = $this->billing->initiateCheckout($this->hotel(), $validated['tier'], $validated['billing_cycle']);
        } catch (\Throwable $e) {
            return back()->withErrors(['tier' => $e->getMessage()]);
        }

        return redirect()->away($result['checkout_url']);
    }

    /** Where Flutterwave/Paystack redirect the browser back to after payment. The webhook is the source of truth; this is just UX. */
    public function callback(Request $request)
    {
        $hotel = $this->hotel()->fresh();

        if ($hotel->subscription_status === 'active') {
            $message = 'Payment confirmed! Your subscription is now active.';

            if (! $hotel->onboarding_completed) {
                return redirect()->route('onboarding.show', ['step' => 3])->with('success', $message);
            }

            return redirect()->route('hotel.dashboard')->with('success', $message);
        }

        return redirect()->route('hotel.subscription.plans')
            ->with('info', "We're still confirming your payment — this can take a minute. Refresh shortly, or contact support if it doesn't update.");
    }

    protected function tierData(): array
    {
        $tiers = [];
        foreach (['starter', 'growth', 'pro'] as $tier) {
            $monthly = Hotel::TIER_MONTHLY_FEES[$tier];
            $tiers[$tier] = [
                'monthly' => $monthly,
                'yearly' => Subscription::amountFor($tier, 'yearly'),
                'yearly_full_price' => $monthly * 12,
            ];
        }
        return $tiers;
    }
}
