<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            return back()->withErrors(['amount' => 'Withdrawal could not be started, please try again later or contact support ']);
        }

        return redirect()->route('hotel.wallet.withdrawals')
            ->with('success', "Withdrawal of ₦".number_format($withdrawal->amountNaira(), 2)." is processing. It will arive at the bank account provided soon");
    }

    public function listBanks()
    {
        $key = config('services.flutterwave.secret_key');
     
        if (blank($key)) {
            // Graceful fallback: return an empty list; the blade retries.
            return response()->json([]);
        }
     
        $banks = Cache::remember('flw_banks_ng', now()->addHours(6), function () use ($key) {
            $response = Http::withToken($key)
                ->get('https://api.flutterwave.com/v3/banks/NG');
     
            if ($response->successful() && $response->json('status') === 'success') {
                return collect($response->json('data'))
                    ->map(fn ($b) => ['name' => $b['name'], 'code' => $b['code']])
                    ->sortBy('name')
                    ->values()
                    ->all();
            }
     
            return [];
        });
     
        return response()->json($banks);
    }

    public function verifyAccount(Request $request)
    {
        $validated = $request->validate([
            'account_number' => ['required', 'string', 'size:10'],
            'bank_code'      => ['required', 'string', 'max:10'],
        ]);

        $key = config('services.flutterwave.secret_key');

        if (blank($key)) {
            return response()->json(['success' => false, 'message' => 'Payment gateway not configured.']);
        }

        try {
            $response = Http::withToken($key)
                ->post('https://api.flutterwave.com/v3/accounts/resolve', [
                    'account_number' => $validated['account_number'],
                    'account_bank'   => $validated['bank_code'],
                ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return response()->json([
                    'success'      => true,
                    'account_name' => $response->json('data.account_name'),
                ]);
            }

            $message = $response->json('message') ?? 'Account could not be verified.';

            // Flutterwave test-mode keys only ever resolve bank code 044 (Access Bank).
            if (str_starts_with($key, 'FLWSECK_TEST') && $validated['bank_code'] !== '044') {
                $message = 'You are using Flutterwave test keys, which only verify Access Bank (044). Use bank code 044 with a Flutterwave test account number, or switch to live keys for real verification.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::error('FLW account verify error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Verification service unavailable.']);
        }
    }
}
