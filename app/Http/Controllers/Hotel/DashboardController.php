<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $hotel = $user->hotel;

        $confirmedPayments = $hotel->payments()->where('status', 'confirmed');

        $totalRooms = $hotel->rooms()->count();
        $occupiedRooms = $hotel->rooms()->where('status', 'occupied')->count();

        $stats = [
            'today_revenue' => (clone $confirmedPayments)->whereDate('paid_at', today())->sum('amount') / 100,
            'week_revenue' => (clone $confirmedPayments)->where('paid_at', '>=', now()->startOfWeek())->sum('amount') / 100,
            'month_revenue' => (clone $confirmedPayments)->where('paid_at', '>=', now()->startOfMonth())->sum('amount') / 100,
            'occupancy_rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0,
            'rooms_available' => $hotel->rooms()->where('status', 'available')->count(),
            'rooms_occupied' => $occupiedRooms,
            'rooms_dirty' => $hotel->rooms()->where('status', 'dirty')->count(),
            'rooms_maintenance' => $hotel->rooms()->where('status', 'maintenance')->count(),
        ];

        $recentBookings = $hotel->bookings()->with(['guest', 'room'])->latest()->take(5)->get();
        $pendingPayments = $hotel->bookings()->where('balance', '>', 0)->whereIn('status', ['checked_in'])->with('guest')->take(5)->get();

        return view('hotel.dashboard', [
            'hotel' => $hotel,
            'user' => $user,
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'pendingPayments' => $pendingPayments,
        ]);
    }
}
