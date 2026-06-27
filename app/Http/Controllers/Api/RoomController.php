<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Read-only API access (Pro tier+). Scoped automatically to the
 * token-owner's hotel — there's no way to pass a different hotel_id and see
 * someone else's data.
 */
class RoomController extends Controller
{
    public function index(Request $request)
    {
        $hotel = $request->user()->hotel;

        $rooms = $hotel->rooms()->with('media')->get()->map(fn ($r) => [
            'id' => $r->id,
            'room_number' => $r->room_number,
            'type' => $r->type,
            'status' => $r->status,
            'price_per_night_naira' => $r->pricePerNightNaira(),
            'max_guests' => $r->max_guests,
            'images' => $r->media->where('type', 'image')->pluck('url')->values(),
        ]);

        return response()->json(['data' => $rooms]);
    }
}
