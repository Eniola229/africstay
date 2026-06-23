<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Forces an owner who hasn't finished the 4-step onboarding wizard
 * back into the wizard before they can reach the main dashboard.
 * Staff (non-owners) are never subject to this — only the owner who
 * registered the hotel walks through onboarding.
 */
class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->isOwner() && $user->hotel && ! $user->hotel->onboarding_completed) {
            if (! $request->routeIs('onboarding.*')) {
                return redirect()->route('onboarding.show', ['step' => $user->hotel->onboarding_step]);
            }
        }

        return $next($request);
    }
}