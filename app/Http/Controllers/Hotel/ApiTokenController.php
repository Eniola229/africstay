<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API access is a Pro/Enterprise tier perk (spec). Owner-only — generates a
 * Sanctum personal access token scoped to read-only abilities. See
 * routes/api.php for what it can actually call.
 */
class ApiTokenController extends Controller
{
    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    public function show()
    {
        $hotel = $this->hotel();

        if (! in_array($hotel->tier, ['pro', 'enterprise'])) {
            return redirect()->route('hotel.subscription.plans')
                ->with('info', 'API access is available on the Pro tier and above.');
        }

        return view('hotel.settings.api', [
            'tokens' => Auth::user()->tokens,
        ]);
    }

    public function generate(Request $request)
    {
        $hotel = $this->hotel();

        if (! Auth::user()->isOwner()) {
            abort(403, 'Only the owner can manage API tokens.');
        }

        if (! in_array($hotel->tier, ['pro', 'enterprise'])) {
            abort(403, 'API access requires the Pro tier or above.');
        }

        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $token = Auth::user()->createToken($request->name, ['read:rooms', 'read:bookings']);

        return back()->with('success', 'New token generated — copy it now, it will not be shown again.')
            ->with('newToken', $token->plainTextToken);
    }

    public function revoke(string $tokenId)
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'Token revoked.');
    }
}
