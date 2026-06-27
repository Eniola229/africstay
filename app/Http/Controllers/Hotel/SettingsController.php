<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    protected function authorizeOwner(): void
    {
        if (! Auth::user()->isOwner()) {
            abort(403, 'Only the hotel owner can change settings.');
        }
    }

    public function show()
    {
        return view('hotel.settings.index', ['hotel' => $this->hotel()]);
    }

    public function updateProfile(Request $request)
    {
        $this->authorizeOwner();
        $hotel = $this->hotel();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo_url' => ['nullable', 'url'],
        ]);

        $old = $hotel->only(['name', 'phone', 'email']);

        $hotel->update([
            ...$validated,
            'logo' => $validated['logo_url'] ?? $hotel->logo,
        ]);

        ActivityLog::record($hotel->id, Auth::user(), 'UPDATE_HOTEL_PROFILE', 'settings', 'Hotel', $hotel->id, $hotel->name,
            Auth::user()->name.' updated the hotel profile.', $old, $hotel->only(['name', 'phone', 'email']));

        return back()->with('success', 'Hotel profile updated.');
    }

    public function updateOnlineBooking(Request $request)
    {
        $this->authorizeOwner();
        $hotel = $this->hotel();

        $validated = $request->validate([
            'online_booking_enabled' => ['nullable', 'boolean'],
            'online_booking_deposit_percent' => ['required', 'integer', 'min:10', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ]);

        $hotel->update([
            'online_booking_enabled' => $request->boolean('online_booking_enabled'),
            'online_booking_deposit_percent' => $validated['online_booking_deposit_percent'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        ActivityLog::record($hotel->id, Auth::user(), 'UPDATE_ONLINE_BOOKING_SETTINGS', 'settings', 'Hotel', $hotel->id, $hotel->name,
            Auth::user()->name." set the online deposit to {$validated['online_booking_deposit_percent']}% and online booking ".($hotel->online_booking_enabled ? 'enabled' : 'disabled').'.');

        return back()->with('success', 'Online booking settings updated.');
    }

    /** Branded booking page — Pro tier and above only (spec: "Branded booking page (hotel logo + colors)"). */
    public function updateBranding(Request $request)
    {
        $this->authorizeOwner();
        $hotel = $this->hotel();

        if (! in_array($hotel->tier, ['pro', 'enterprise'])) {
            abort(403, 'Branded booking pages require the Pro tier or above.');
        }

        $validated = $request->validate([
            'brand_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $hotel->update($validated);

        ActivityLog::record($hotel->id, Auth::user(), 'UPDATE_BRANDING', 'settings', 'Hotel', $hotel->id, $hotel->name,
            Auth::user()->name." updated the booking page brand color to {$validated['brand_primary_color']}.");

        return back()->with('success', 'Branding updated.');
    }
}
