<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * When a platform admin is impersonating a hotel (session flag set by
 * HotelManagementController::impersonate()), block every write request.
 * Read (GET) requests pass through normally so the admin can browse the
 * hotel's dashboard exactly as the hotel sees it — "PLATFORM VIEW — READ
 * ONLY" watermark is rendered by layouts/hotel.blade.php when this flag is set.
 */
class ImpersonationReadOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (session('platform_impersonating_hotel_id') && ! $request->isMethod('GET')) {
            // Allow the "stop impersonating" action itself through.
            if (! $request->routeIs('platform.hotels.stop-impersonating')) {
                abort(403, 'This is a read-only platform view — no changes can be made while impersonating.');
            }
        }

        return $next($request);
    }
}
