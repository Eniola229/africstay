<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\SubscriptionPayment;
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

        $from = $request->query('from') ? \Carbon\Carbon::parse($request->query('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->query('to') ? \Carbon\Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        // AfricStay's revenue = subscription payments (its own SaaS fee) +
        // the per-transaction fee skimmed off every guest payment. We track
        // the latter implicitly via Hotel::TIER_TRANSACTION_FEE_PERCENT since
        // there's no separate "platform_fee_ledger" table — computed here.
        $subscriptionRevenue = SubscriptionPayment::where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])->sum('amount');

        $transactionFeeRevenue = 0;
        $feesByHotel = [];
        foreach (Hotel::whereNull('parent_hotel_id')->get() as $hotel) {
            $feePercent = Hotel::TIER_TRANSACTION_FEE_PERCENT[$hotel->tier] ?? 1.5;
            $gross = $hotel->payments()->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])->sum('amount');
            $fee = (int) round($gross * ($feePercent / 100));
            $transactionFeeRevenue += $fee;
            if ($fee > 0) {
                $feesByHotel[] = ['hotel' => $hotel, 'fee' => $fee, 'gross' => $gross];
            }
        }
        usort($feesByHotel, fn ($a, $b) => $b['fee'] <=> $a['fee']);

        $byTier = Hotel::whereNull('parent_hotel_id')->select('tier', DB::raw('COUNT(*) as count'))->groupBy('tier')->pluck('count', 'tier');

        $mrr = Hotel::whereNull('parent_hotel_id')->where('subscription_status', 'active')
            ->get()
            ->sum(fn (Hotel $h) => Hotel::TIER_MONTHLY_FEES[$h->tier] ?? 0);

        $churned = Hotel::whereNull('parent_hotel_id')->whereIn('subscription_status', ['expired', 'cancelled'])
            ->where('updated_at', '>=', now()->subDays(90))->get();

        return view('platform.revenue.index', [
            'from' => $from, 'to' => $to,
            'subscriptionRevenue' => $subscriptionRevenue,
            'transactionFeeRevenue' => $transactionFeeRevenue,
            'feesByHotel' => array_slice($feesByHotel, 0, 10),
            'byTier' => $byTier,
            'mrr' => $mrr,
            'churned' => $churned,
            'totalActiveSubscriptions' => Hotel::whereNull('parent_hotel_id')->where('subscription_status', 'active')->count(),
        ]);
    }

    /** finance: withdrawal oversight across ALL hotels. */
    public function withdrawals(Request $request)
    {
        $this->authorizeRole(['super_admin', 'finance']);

        $query = \App\Models\Withdrawal::with('hotel')->latest();
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('platform.revenue.withdrawals', [
            'withdrawals' => $query->paginate(30)->withQueryString(),
            'currentStatus' => $request->query('status', 'all'),
        ]);
    }

    protected function authorizeRole(array $roles): void
    {
        if (! in_array(Auth::guard('platform')->user()->role, $roles)) {
            abort(403);
        }
    }
}
