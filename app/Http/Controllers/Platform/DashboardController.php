<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseInquiry;
use App\Models\Hotel;
use App\Models\PlatformAdmin;
use App\Models\SubscriptionPayment;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Hotel stats ──────────────────────────────────────────────
        $hotelStats = [
            'total'           => Hotel::whereNull('parent_hotel_id')->count(),
            'active'          => Hotel::whereNull('parent_hotel_id')->where('subscription_status', 'active')->count(),
            'pending_payment' => Hotel::whereNull('parent_hotel_id')->where('subscription_status', 'pending_payment')->count(),
            'past_due'        => Hotel::whereNull('parent_hotel_id')->where('subscription_status', 'past_due')->count(),
            'expired'         => Hotel::whereNull('parent_hotel_id')->where('subscription_status', 'expired')->count(),
            'new_this_month'  => Hotel::whereNull('parent_hotel_id')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        // ── Revenue stats ────────────────────────────────────────────
        $mrr = Hotel::whereNull('parent_hotel_id')
            ->where('subscription_status', 'active')
            ->get()
            ->sum(fn (Hotel $h) => Hotel::TIER_MONTHLY_FEES[$h->tier] ?? 0);

        $revenueThisMonth = SubscriptionPayment::where('status', 'confirmed')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $revenueLastMonth = SubscriptionPayment::where('status', 'confirmed')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('amount');

        // ── Withdrawal stats ─────────────────────────────────────────
        $withdrawalStats = [
            'pending_count'    => Withdrawal::where('status', 'pending')->count(),
            'pending_amount'   => Withdrawal::where('status', 'pending')->sum('amount'),
            'processing_count' => Withdrawal::where('status', 'processing')->count(),
        ];

        // ── Inquiry stats ────────────────────────────────────────────
        $inquiryStats = [
            'new'       => EnterpriseInquiry::where('status', 'new')->count(),
            'total'     => EnterpriseInquiry::count(),
            'converted' => EnterpriseInquiry::where('status', 'converted')->count(),
        ];

        // ── Tier breakdown ───────────────────────────────────────────
        $byTier = Hotel::whereNull('parent_hotel_id')
            ->selectRaw('tier, COUNT(*) as count')
            ->groupBy('tier')
            ->pluck('count', 'tier');

        // ── Recent hotels ────────────────────────────────────────────
        $recentHotels = Hotel::whereNull('parent_hotel_id')
            ->with('owner')
            ->latest()
            ->take(5)
            ->get();

        // ── Recent activity ──────────────────────────────────────────
        $recentActivity = \App\Models\PlatformActivityLog::with('admin')
            ->latest()
            ->take(8)
            ->get();

        $role = Auth::guard('platform')->user()->role;

        return view('platform.dashboard', compact(
            'hotelStats',
            'mrr',
            'revenueThisMonth',
            'revenueLastMonth',
            'withdrawalStats',
            'inquiryStats',
            'byTier',
            'recentHotels',
            'recentActivity',
            'role'
        ));
    }
}