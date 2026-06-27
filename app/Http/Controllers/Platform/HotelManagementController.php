<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\PlatformActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * support: view + read-only impersonation only.
 * operations: activate/deactivate, tier changes, hotel list.
 * super_admin: everything.
 */
class HotelManagementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRole(['super_admin', 'operations', 'support', 'finance']);

        $query = Hotel::whereNull('parent_hotel_id')->with('owner');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($tier = $request->query('tier')) {
            $query->where('tier', $tier);
        }
        if ($status = $request->query('subscription_status')) {
            $query->where('subscription_status', $status);
        }

        // ── Stats (always unfiltered — full picture at a glance) ────
        $base = Hotel::whereNull('parent_hotel_id');

        $countByStatus = (clone $base)
            ->selectRaw('subscription_status, COUNT(*) as count')
            ->groupBy('subscription_status')
            ->pluck('count', 'subscription_status');

        return view('platform.hotels.index', [
            'hotels'        => $query->latest()->paginate(20)->withQueryString(),
            'countByStatus' => $countByStatus,
        ]);
    }

    public function show(string $hotel)
    {
        $this->authorizeRole(['super_admin', 'operations', 'support', 'finance']);
        $hotel = Hotel::with(['owner', 'subscriptions', 'childLocations'])->findOrFail($hotel);

        return view('platform.hotels.show', [
            'hotel'       => $hotel,
            'payments'    => $hotel->payments()->latest()->take(20)->get(),
            'withdrawals' => $hotel->withdrawals()->latest()->take(10)->get(),
        ]);
    }

    public function toggleActive(string $hotel)
    {
        $this->authorizeRole(['super_admin', 'operations']);
        $hotel = Hotel::findOrFail($hotel);
        $old = $hotel->is_active;

        $hotel->update(['is_active' => ! $hotel->is_active]);

        PlatformActivityLog::record(
            Auth::guard('platform')->user(),
            $hotel->is_active ? 'ACTIVATE_HOTEL' : 'DEACTIVATE_HOTEL',
            'hotel_management',
            'Hotel', $hotel->id, $hotel->name,
            Auth::guard('platform')->user()->name . ' ' . ($hotel->is_active ? 'activated' : 'deactivated') . " {$hotel->name}.",
            ['is_active' => $old],
            ['is_active' => $hotel->is_active]
        );

        return back()->with('success', "{$hotel->name} has been " . ($hotel->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function changeTier(Request $request, string $hotel)
    {
        $this->authorizeRole(['super_admin', 'operations']);
        $hotel = Hotel::findOrFail($hotel);

        $validated = $request->validate([
            'tier'   => ['required', 'in:starter,growth,pro,enterprise'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $old = $hotel->tier;
        $hotel->update(['tier' => $validated['tier']]);

        PlatformActivityLog::record(
            Auth::guard('platform')->user(),
            'CHANGE_TIER',
            'hotel_management',
            'Hotel', $hotel->id, $hotel->name,
            Auth::guard('platform')->user()->name . " changed tier for {$hotel->name} from {$old} to {$validated['tier']}. Reason: {$validated['reason']}.",
            ['tier' => $old],
            ['tier' => $validated['tier']]
        );

        return back()->with('success', "Tier changed to {$validated['tier']}.");
    }

    /**
     * Read-only impersonation. Platform admins authenticate on the `platform`
     * guard, which has no access to hotel routes (those require `auth:web`) —
     * so impersonating means ALSO logging into the `web` guard as the hotel's
     * owner, same browser, both sessions active simultaneously (spec: "a
     * platform admin can be logged into both in the same browser with no
     * conflict"). ImpersonationReadOnly middleware then blocks every
     * non-GET request and the hotel layout shows the watermark banner.
     */
    public function impersonate(string $hotel)
    {
        $this->authorizeRole(['super_admin', 'support']);
        $hotel = Hotel::with('owner')->findOrFail($hotel);

        if (! $hotel->owner) {
            return back()->withErrors(['hotel' => 'This hotel has no owner account to impersonate.']);
        }

        \Illuminate\Support\Facades\Auth::guard('web')->login($hotel->owner);
        session(['platform_impersonating_hotel_id' => $hotel->id]);

        PlatformActivityLog::record(
            Auth::guard('platform')->user(),
            'IMPERSONATE_HOTEL',
            'impersonation',
            'Hotel', $hotel->id, $hotel->name,
            Auth::guard('platform')->user()->name . " started read-only impersonation of {$hotel->name}."
        );

        return redirect()->route('hotel.dashboard');
    }

    public function stopImpersonating()
    {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        session()->forget('platform_impersonating_hotel_id');
        return redirect()->route('platform.hotels.index');
    }

    protected function authorizeRole(array $roles): void
    {
        if (! in_array(Auth::guard('platform')->user()->role, $roles)) {
            abort(403);
        }
    }
}