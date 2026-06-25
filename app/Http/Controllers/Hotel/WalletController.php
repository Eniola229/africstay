<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(protected WithdrawalService $withdrawals) {}

    protected function hotel(): Hotel
    {
        return Auth::user()->hotel;
    }

    public function index()
    {
        $hotel = $this->hotel();

        return view('hotel.wallet.index', [
            'hotel' => $hotel,
            'payments' => $hotel->payments()->where('status', 'confirmed')->with('booking.guest')->latest()->paginate(15),
            'withdrawalHistory' => $hotel->withdrawals()->latest()->take(10)->get(),
            'canWithdraw' => Auth::user()->canWithdrawFunds(),
        ]);
    }

    public function withdrawals()
    {
        $hotel = $this->hotel();

        return view('hotel.wallet.withdrawals', [
            'hotel' => $hotel,
            'withdrawals' => $hotel->withdrawals()->latest()->paginate(20),
        ]);
    }

    public function storeWithdrawal(Request $request)
    {
        if (! Auth::user()->canWithdrawFunds()) {
            abort(403, 'Only the hotel owner can request withdrawals.');
        }

        $hotel = $this->hotel();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10000'], // naira, spec minimum ₦10,000
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_code' => ['required', 'string', 'max:10'],
            'account_number' => ['required', 'string', 'max:20'],
            'account_name' => ['required', 'string', 'max:150'],
        ]);

        $amountKobo = (int) round($validated['amount'] * 100);

        try {
            $withdrawal = $this->withdrawals->initiate(
                $hotel,
                $amountKobo,
                $validated['bank_name'],
                $validated['bank_code'],
                $validated['account_number'],
                $validated['account_name'],
                Auth::id(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        ActivityLog::record($hotel->id, Auth::user(), 'PROCESS_WITHDRAWAL', 'withdrawal', 'Withdrawal', $withdrawal->id,
            $withdrawal->reference,
            Auth::user()->name." requested a withdrawal of ₦".number_format($withdrawal->amountNaira(), 2)." to {$withdrawal->account_number} ({$withdrawal->bank_name}). Status: {$withdrawal->status}.");

        if ($withdrawal->status === 'failed') {
            return back()->withErrors(['amount' => 'Withdrawal could not be started: '.$withdrawal->failure_reason]);
        }

        return redirect()->route('hotel.wallet.withdrawals')
            ->with('success', "Withdrawal of ₦".number_format($withdrawal->amountNaira(), 2)." is processing.");
    }
}
