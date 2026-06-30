<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * If this user belongs to a CHILD location and the parent hotel has dropped
 * below Pro tier, the location goes READ-ONLY rather than a hard lockout —
 * staff can still log in and view existing data, but any write (POST/PUT/
 * PATCH/DELETE) is blocked with an upgrade prompt. Keeps historical bookings
 * visible instead of orphaning them.
 */
class EnsureLocationTierAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! $user->hotel) {
            return $next($request);
        }

        $hotel = $user->hotel;

        if ($hotel->isPrimaryLocation() || $hotel->parentHotel->tier === 'pro') {
            return $next($request);
        }

        // Past this point: child location, parent no longer Pro.
        View::share('locationReadOnly', true);
        View::share('locationReadOnlyMessage',
            'This location is read-only because the hotel account is no longer on the Pro plan. Upgrade to Pro to resume managing this location.');

        if (! $request->isMethod('get')) {
            return back()->withErrors([
                'location' => 'This location is read-only on your current plan. Upgrade to Pro to make changes here.',
            ]);
        }

        return $next($request);
    }
}