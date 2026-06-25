<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestController extends Controller
{
    /** Autocomplete search by phone or name — used on the new-booking form. */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $hotelId = Auth::user()->hotel_id;

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $guests = Guest::where('hotel_id', $hotelId)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email']);

        return response()->json($guests);
    }
}
