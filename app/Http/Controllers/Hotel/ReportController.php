<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * All reports accept ?from=YYYY-MM-DD&to=YYYY-MM-DD (defaults to this month).
 * Every report has a matching exportCsv()/exportPdf() pair.
 *
 * PDF export uses barryvdh/laravel-dompdf — add it with:
 *   composer require barryvdh/laravel-dompdf
 * (not auto-installed here since this drop doesn't run composer). CSV export
 * has no dependency — it's plain PHP fputcsv, opens fine in Excel/Sheets.
 */
class ReportController extends Controller
{
    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    protected function dateRange(Request $request): array
    {
        $from = $request->query('from') ? \Carbon\Carbon::parse($request->query('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->query('to') ? \Carbon\Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }

    public function index()
    {
        return view('hotel.reports.index');
    }

    /* ---------------------------------------------------------------- */
    /* OPERATIONAL REPORTS                                               */
    /* ---------------------------------------------------------------- */

    public function arrivalsDepartures(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $hotel = $this->hotel();

        $arrivals = $hotel->bookings()->with(['guest', 'room'])
            ->whereBetween('check_in', [$from, $to])->whereIn('status', ['confirmed', 'checked_in'])->get();
        $departures = $hotel->bookings()->with(['guest', 'room'])
            ->whereBetween('check_out', [$from, $to])->whereIn('status', ['checked_in', 'checked_out'])->get();

        return view('hotel.reports.arrivals-departures', compact('arrivals', 'departures', 'from', 'to'));
    }

    public function occupiedRooms()
    {
        $rooms = $this->hotel()->rooms()->where('status', 'occupied')
            ->with(['bookings' => fn ($q) => $q->where('status', 'checked_in')->with('guest')])
            ->get();

        return view('hotel.reports.occupied-rooms', compact('rooms'));
    }

    public function outstandingBalances()
    {
        $bookings = $this->hotel()->bookings()->where('balance', '>', 0)
            ->whereIn('status', ['checked_in', 'checked_out'])->with(['guest', 'room'])->latest()->get();

        return view('hotel.reports.outstanding-balances', compact('bookings'));
    }

    public function housekeepingStatus()
    {
        $tasks = $this->hotel()->housekeepingTasks()->with(['room', 'assignee'])->latest()->get();

        return view('hotel.reports.housekeeping-status', compact('tasks'));
    }

    public function roomServiceOrders()
    {
        $orders = $this->hotel()->roomServiceOrders()->whereIn('status', ['pending', 'in_progress'])
            ->with(['item', 'booking.guest', 'booking.room'])->latest()->get();

        return view('hotel.reports.room-service-orders', compact('orders'));
    }

    /* ---------------------------------------------------------------- */
    /* FINANCIAL REPORTS                                                 */
    /* ---------------------------------------------------------------- */

    public function revenueBreakdown(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $hotel = $this->hotel();

        $roomRevenue = $hotel->bookings()->whereBetween('checked_out_at', [$from, $to])->sum('total_amount')
            - $hotel->roomServiceOrders()->whereHas('booking', fn ($q) => $q->whereBetween('checked_out_at', [$from, $to]))->sum('total_price');
        $extrasRevenue = $hotel->roomServiceOrders()->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$from, $to])->sum('total_price');
        $totalConfirmedPayments = $hotel->payments()->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])->sum('amount');

        return view('hotel.reports.revenue-breakdown', compact('roomRevenue', 'extrasRevenue', 'totalConfirmedPayments', 'from', 'to'));
    }

    public function paymentsByMethod(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $breakdown = $this->hotel()->payments()->where('status', 'confirmed')
            ->whereBetween('paid_at', [$from, $to])
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')->get();

        return view('hotel.reports.payments-by-method', compact('breakdown', 'from', 'to'));
    }

    public function transactionFees(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $hotel = $this->hotel();
        $feePercent = Hotel::TIER_TRANSACTION_FEE_PERCENT[$hotel->tier] ?? 1.5;

        $grossTotal = $hotel->payments()->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])->sum('amount');
        $feesDeducted = (int) round($grossTotal * ($feePercent / 100));
        $netCredited = $grossTotal - $feesDeducted;

        return view('hotel.reports.transaction-fees', compact('grossTotal', 'feesDeducted', 'netCredited', 'feePercent', 'from', 'to'));
    }

    public function walletHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $hotel = $this->hotel();

        $credits = $hotel->payments()->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])->latest('paid_at')->get();
        $debits = $hotel->withdrawals()->whereBetween('created_at', [$from, $to])->latest()->get();

        return view('hotel.reports.wallet-history', compact('credits', 'debits', 'from', 'to'));
    }

    public function withdrawalHistory()
    {
        $withdrawals = $this->hotel()->withdrawals()->latest()->paginate(30);

        return view('hotel.reports.withdrawal-history', compact('withdrawals'));
    }

    public function profitAndLoss(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $hotel = $this->hotel();
        $feePercent = Hotel::TIER_TRANSACTION_FEE_PERCENT[$hotel->tier] ?? 1.5;

        $totalRevenue = $hotel->payments()->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])->sum('amount');
        $feesDeducted = (int) round($totalRevenue * ($feePercent / 100));
        $withdrawalsTotal = $hotel->withdrawals()->where('status', 'completed')->whereBetween('created_at', [$from, $to])->sum('amount');
        $closingBalance = $hotel->wallet_balance;

        return view('hotel.reports.profit-and-loss', compact('totalRevenue', 'feesDeducted', 'withdrawalsTotal', 'closingBalance', 'from', 'to'));
    }

    /* ---------------------------------------------------------------- */
    /* EXPORTS                                                           */
    /* ---------------------------------------------------------------- */

    /** Generic CSV export — pass a report key, reuses the same query logic as the on-screen reports. */
    public function exportCsv(Request $request, string $report): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $hotel = $this->hotel();

        $filename = "{$report}-".now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($report, $hotel, $from, $to) {
            $handle = fopen('php://output', 'w');

            match ($report) {
                'outstanding-balances' => $this->csvOutstandingBalances($handle, $hotel),
                'payments-by-method' => $this->csvPaymentsByMethod($handle, $hotel, $from, $to),
                'withdrawal-history' => $this->csvWithdrawalHistory($handle, $hotel),
                default => fputcsv($handle, ['Unknown report type']),
            };

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function csvOutstandingBalances($handle, Hotel $hotel): void
    {
        fputcsv($handle, ['Booking Ref', 'Guest', 'Room', 'Total', 'Paid', 'Balance']);
        foreach ($hotel->bookings()->where('balance', '>', 0)->with(['guest', 'room'])->get() as $b) {
            fputcsv($handle, [$b->booking_reference, $b->guest->name, $b->room->room_number, $b->totalAmountNaira(), $b->amountPaidNaira(), $b->balanceNaira()]);
        }
    }

    protected function csvPaymentsByMethod($handle, Hotel $hotel, $from, $to): void
    {
        fputcsv($handle, ['Method', 'Count', 'Total (NGN)']);
        $rows = $hotel->payments()->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))->groupBy('payment_method')->get();
        foreach ($rows as $r) {
            fputcsv($handle, [$r->payment_method, $r->count, $r->total / 100]);
        }
    }

    protected function csvWithdrawalHistory($handle, Hotel $hotel): void
    {
        fputcsv($handle, ['Reference', 'Amount (NGN)', 'Bank', 'Account', 'Status', 'Date']);
        foreach ($hotel->withdrawals()->latest()->get() as $w) {
            fputcsv($handle, [$w->reference, $w->amountNaira(), $w->bank_name, $w->account_number, $w->status, $w->created_at->format('Y-m-d H:i')]);
        }
    }

    /**
     * PDF export — requires barryvdh/laravel-dompdf (see class docblock).
     * If that package isn't installed, this throws a clear error rather than
     * silently failing, so it's obvious what to add.
     */
    public function exportPdf(Request $request, string $report)
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            abort(500, 'PDF export requires the barryvdh/laravel-dompdf package. Run: composer require barryvdh/laravel-dompdf');
        }

        [$from, $to] = $this->dateRange($request);
        $hotel = $this->hotel();

        $view = match ($report) {
            'outstanding-balances' => 'hotel.reports.pdf.outstanding-balances',
            'profit-and-loss' => 'hotel.reports.pdf.profit-and-loss',
            default => abort(404, 'Unknown report.'),
        };

        $data = match ($report) {
            'outstanding-balances' => ['hotel' => $hotel, 'bookings' => $hotel->bookings()->where('balance', '>', 0)->with(['guest', 'room'])->get()],
            'profit-and-loss' => $this->plDataFor($hotel, $from, $to),
            default => [],
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);

        return $pdf->download("{$report}-".now()->format('Ymd').'.pdf');
    }

    protected function plDataFor(Hotel $hotel, $from, $to): array
    {
        $feePercent = Hotel::TIER_TRANSACTION_FEE_PERCENT[$hotel->tier] ?? 1.5;
        $totalRevenue = $hotel->payments()->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to])->sum('amount');

        return [
            'hotel' => $hotel,
            'totalRevenue' => $totalRevenue,
            'feesDeducted' => (int) round($totalRevenue * ($feePercent / 100)),
            'withdrawalsTotal' => $hotel->withdrawals()->where('status', 'completed')->whereBetween('created_at', [$from, $to])->sum('amount'),
            'closingBalance' => $hotel->wallet_balance,
            'from' => $from,
            'to' => $to,
        ];
    }
}
