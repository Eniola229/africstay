<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Blocks access to the hotel dashboard/operational routes unless the hotel
 * has an active (or grace-period past_due) subscription.
 *
 *  - pending_payment -> never paid yet -> send to the plan picker
 *  - active           -> full access
 *  - past_due         -> within Hotel::GRACE_PERIOD_DAYS of expiry -> allow access,
 *                        a warning banner is shown (handled in the dashboard layout)
 *  - expired/cancelled -> hard lockout -> renewal screen
 *
 * Platform admins are never subject to this — they use a completely separate guard.
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();

        if (! $user || ! $user->hotel) {
            return $next($request);
        }

        $hotel = $user->hotel;

        if ($hotel->subscription_status === 'pending_payment') {
            if (! $request->routeIs('hotel.subscription.*') && ! $request->routeIs('onboarding.*')) {
                return redirect()->route('hotel.subscription.plans')
                    ->with('info', 'Choose a plan and complete payment to activate your AfricStay account.');
            }
        }

        if (in_array($hotel->subscription_status, ['expired', 'cancelled'])) {
            if (! $request->routeIs('hotel.subscription.*')) {
                return redirect()->route('hotel.subscription.plans')
                    ->with('warning', 'Your AfricStay subscription has expired. Renew to regain access.');
            }
        }

        return $next($request);
    }
}
