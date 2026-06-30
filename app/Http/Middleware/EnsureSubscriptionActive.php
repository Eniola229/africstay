<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! $user->hotel) {
            return $next($request);
        }

        $hotel = $user->hotel;
        $status = $hotel->effectiveSubscriptionStatus();

        if ($status === 'pending_payment') {
            if (! $request->routeIs('hotel.subscription.*') && ! $request->routeIs('onboarding.*')) {
                return redirect()->route('hotel.subscription.plans')
                    ->with('info', 'Choose a plan and complete payment to activate your AfricStay account.');
            }
        }

        if (in_array($status, ['expired', 'cancelled'])) {
            if (! $request->routeIs('hotel.subscription.*')) {
                return redirect()->route('hotel.subscription.plans')
                    ->with('warning', 'Your AfricStay subscription has expired. Renew to regain access.');
            }
        }

        return $next($request);
    }
}