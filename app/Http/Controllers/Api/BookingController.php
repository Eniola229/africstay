<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $hotel = $request->user()->hotel;

        $bookings = $hotel->bookings()->with(['guest', 'room'])->latest()->paginate(20);

        return response()->json([
            'data' => $bookings->map(fn ($b) => [
                'id' => $b->id,
                'reference' => $b->booking_reference,
                'guest_name' => $b->guest->name,
                'room_number' => $b->room->room_number,
                'check_in' => $b->check_in->toDateString(),
                'check_out' => $b->check_out->toDateString(),
                'status' => $b->status,
                'total_amount_naira' => $b->totalAmountNaira(),
                'balance_naira' => $b->balanceNaira(),
            ]),
            'meta' => ['current_page' => $bookings->currentPage(), 'last_page' => $bookings->lastPage()],
        ]);
    }
}
