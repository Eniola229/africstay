<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\SubscriptionPayment;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * finance: this whole report + withdrawal oversight.
 * super_admin: same, plus everything else.
 */
class RevenueReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRole(['super_admin', 'finance']);

        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        // ── Subscription revenue ─────────────────────────────────────
        $subscriptionRevenue = SubscriptionPayment::where('status', 'confirmed')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        // Previous period (same duration) for comparison
        $periodDays = $from->diffInDays($to) + 1;
        $prevFrom   = $from->copy()->subDays($periodDays);
        $prevTo     = $from->copy()->subSecond();

        $prevSubscriptionRevenue = SubscriptionPayment::where('status', 'confirmed')
            ->whereBetween('paid_at', [$prevFrom, $prevTo])
            ->sum('amount');

        // ── Transaction fee revenue ──────────────────────────────────
        $transactionFeeRevenue = 0;
        $feesByHotel = [];

        foreach (Hotel::whereNull('parent_hotel_id')->get() as $hotel) {
            $feePercent = Hotel::TIER_TRANSACTION_FEE_PERCENT[$hotel->tier] ?? 1.5;
            $gross      = $hotel->payments()
                ->where('status', 'confirmed')
                ->whereBetween('paid_at', [$from, $to])
                ->sum('amount');
            $fee = (int) round($gross * ($feePercent / 100));
            $transactionFeeRevenue += $fee;

            if ($fee > 0) {
                $feesByHotel[] = ['hotel' => $hotel, 'fee' => $fee, 'gross' => $gross];
            }
        }

        usort($feesByHotel, fn ($a, $b) => $b['fee'] <=> $a['fee']);

        $prevTransactionFeeRevenue = 0;
        foreach (Hotel::whereNull('parent_hotel_id')->get() as $hotel) {
            $feePercent = Hotel::TIER_TRANSACTION_FEE_PERCENT[$hotel->tier] ?? 1.5;
            $gross      = $hotel->payments()
                ->where('status', 'confirmed')
                ->whereBetween('paid_at', [$prevFrom, $prevTo])
                ->sum('amount');
            $prevTransactionFeeRevenue += (int) round($gross * ($feePercent / 100));
        }

        // ── By tier breakdown ────────────────────────────────────────
        $byTier = Hotel::whereNull('parent_hotel_id')
            ->select('tier', DB::raw('COUNT(*) as count'))
            ->groupBy('tier')
            ->pluck('count', 'tier');

        // ── MRR ─────────────────────────────────────────────────────
        $mrr = Hotel::whereNull('parent_hotel_id')
            ->where('subscription_status', 'active')
            ->get()
            ->sum(fn (Hotel $h) => Hotel::TIER_MONTHLY_FEES[$h->tier] ?? 0);

        // ── Churn (last 90 days) ─────────────────────────────────────
        $churned = Hotel::whereNull('parent_hotel_id')
            ->whereIn('subscription_status', ['expired', 'cancelled'])
            ->where('updated_at', '>=', now()->subDays(90))
            ->get();

        // ── Withdrawals overview ─────────────────────────────────────
        $withdrawalStats = [
            'pending'    => Withdrawal::where('status', 'pending')->sum('amount'),
            'processing' => Withdrawal::where('status', 'processing')->sum('amount'),
            'completed'  => Withdrawal::where('status', 'completed')
                ->whereBetween('processed_at', [$from, $to])
                ->sum('amount'),
            'failed'     => Withdrawal::where('status', 'failed')
                ->whereBetween('created_at', [$from, $to])
                ->count(),
        ];

        // ── Sub payment counts ───────────────────────────────────────
        $subPaymentCounts = SubscriptionPayment::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('platform.revenue.index', [
            'from'                     => $from,
            'to'                       => $to,
            'subscriptionRevenue'      => $subscriptionRevenue,
            'prevSubscriptionRevenue'  => $prevSubscriptionRevenue,
            'transactionFeeRevenue'    => $transactionFeeRevenue,
            'prevTransactionFeeRevenue'=> $prevTransactionFeeRevenue,
            'feesByHotel'              => array_slice($feesByHotel, 0, 10),
            'byTier'                   => $byTier,
            'mrr'                      => $mrr,
            'churned'                  => $churned,
            'totalActiveSubscriptions' => Hotel::whereNull('parent_hotel_id')->where('subscription_status', 'active')->count(),
            'withdrawalStats'          => $withdrawalStats,
            'subPaymentCounts'         => $subPaymentCounts,
            'periodDays'               => $periodDays,
        ]);
    }

    /** finance: withdrawal oversight across ALL hotels. */
    public function withdrawals(Request $request)
    {
        $this->authorizeRole(['super_admin', 'finance']);

        $query = Withdrawal::with('hotel')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('search')) {
            $query->whereHas('hotel', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        // Stats
        $stats = [
            'total'      => Withdrawal::count(),
            'pending'    => Withdrawal::where('status', 'pending')->count(),
            'processing' => Withdrawal::where('status', 'processing')->count(),
            'completed'  => Withdrawal::where('status', 'completed')->count(),
            'failed'     => Withdrawal::where('status', 'failed')->count(),
            'pending_amount'    => Withdrawal::where('status', 'pending')->sum('amount'),
            'processing_amount' => Withdrawal::where('status', 'processing')->sum('amount'),
        ];

        return view('platform.revenue.withdrawals', [
            'withdrawals'   => $query->paginate(30)->withQueryString(),
            'currentStatus' => $request->query('status', 'all'),
            'stats'         => $stats,
        ]);
    }

    protected function authorizeRole(array $roles): void
    {
        if (! in_array(Auth::guard('platform')->user()->role, $roles)) {
            abort(403);
        }
    }
}