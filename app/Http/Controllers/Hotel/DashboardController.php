<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $hotel = $user->hotel;

        // Placeholder stats — wired to real bookings/payments in Phase 2/4.
        $stats = [
            'today_revenue' => 0,
            'week_revenue' => 0,
            'month_revenue' => 0,
            'occupancy_rate' => 0,
            'rooms_available' => \DB::table('rooms')->where('hotel_id', $hotel->id)->where('status', 'available')->count(),
            'rooms_occupied' => \DB::table('rooms')->where('hotel_id', $hotel->id)->where('status', 'occupied')->count(),
            'rooms_dirty' => \DB::table('rooms')->where('hotel_id', $hotel->id)->where('status', 'dirty')->count(),
            'rooms_maintenance' => \DB::table('rooms')->where('hotel_id', $hotel->id)->where('status', 'maintenance')->count(),
        ];

        return view('hotel.dashboard', ['hotel' => $hotel, 'user' => $user, 'stats' => $stats]);
    }
}
