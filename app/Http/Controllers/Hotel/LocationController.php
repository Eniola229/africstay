<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Pro tier: "up to 3 locations" + a multi-location revenue dashboard (spec).
 * A child location is its own `hotels` row (own rooms/bookings/wallet — each
 * location's finances stay separate), linked back via parent_hotel_id. The
 * owner's login always points at the PRIMARY hotel; this page aggregates
 * read-only stats across primary + children.
 *
 * NOTE: this drop wires up adding locations and the aggregated dashboard.
 * Actually switching into a child location to manage ITS rooms/bookings day
 * to day isn't wired yet — see README "Still open" for what that would need.
 */
class LocationController extends Controller
{
    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    public function index()
    {
        $hotel = $this->hotel();

        if ($hotel->tier !== 'pro') {
            return redirect()->route('hotel.subscription.plans')
                ->with('info', 'Multi-location management is a Pro tier feature. Upgrade to add more locations.');
        }

        $locationIds = $hotel->allLocationIds();
        $locations = Hotel::whereIn('id', $locationIds)->get();

        $aggregated = $locations->map(function (Hotel $loc) {
            return [
                'hotel' => $loc,
                'rooms' => $loc->rooms()->count(),
                'occupied' => $loc->rooms()->where('status', 'occupied')->count(),
                'wallet_balance' => $loc->wallet_balance,
                'month_revenue' => $loc->payments()->where('status', 'confirmed')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            ];
        });

        return view('hotel.locations.index', [
            'hotel' => $hotel,
            'aggregated' => $aggregated,
            'canAddLocation' => $hotel->canAddLocation(),
        ]);
    }

    public function create()
    {
        $hotel = $this->hotel();

        if (! $hotel->canAddLocation()) {
            return redirect()->route('hotel.locations.index')
                ->withErrors(['location' => "You've reached the maximum of ".Hotel::MAX_LOCATIONS_PRO.' locations for the Pro tier.']);
        }

        return view('hotel.locations.create');
    }

    public function store(Request $request)
    {
        $hotel = $this->hotel();
        $this->authorizeOwner();

        if (! $hotel->canAddLocation()) {
            return back()->withErrors(['location' => "You've reached the maximum of ".Hotel::MAX_LOCATIONS_PRO.' locations for the Pro tier.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug;
        $i = 1;
        while (Hotel::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        $location = Hotel::create([
            ...$validated,
            'slug' => $slug,
            'tier' => 'pro', // child locations inherit the parent's tier — billed under one subscription
            'owner_id' => $hotel->owner_id,
            'parent_hotel_id' => $hotel->id,
            'is_active' => true,
            'subscription_status' => 'active', // covered by the primary hotel's subscription
            'onboarding_completed' => true,
            'onboarding_step' => 5,
        ]);

        ActivityLog::record($hotel->id, Auth::user(), 'CREATE_LOCATION', 'settings', 'Hotel', $location->id, $location->name,
            Auth::user()->name." added a new location: {$location->name}.");

        return redirect()->route('hotel.locations.index')->with('success', "Location \"{$location->name}\" added.");
    }

    protected function authorizeOwner(): void
    {
        if (! Auth::user()->isOwner()) {
            abort(403, 'Only the hotel owner can manage locations.');
        }
    }
}
