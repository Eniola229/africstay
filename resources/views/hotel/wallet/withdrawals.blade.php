@extends('layouts.hotel')
@section('title', 'Withdrawals')
@section('page_title', 'Withdrawals')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.wallet.index') }}">Wallet</a></li>
    <li class="breadcrumb-item active">Withdrawals</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Request a Withdrawal</h5></div>
            <div class="card-body">
                <p class="text-muted fs-13">Available balance: <strong>₦{{ number_format($hotel->walletBalanceNaira(), 2) }}</strong></p>
                <form action="{{ route('hotel.wallet.withdrawals.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount (₦)</label>
                        <input type="number" name="amount" min="10000" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Minimum ₦10,000</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Bank name</label>
                        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" required>
                        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Bank code</label>
                        <input type="text" name="bank_code" class="form-control @error('bank_code') is-invalid @enderror" placeholder="e.g. 044" required>
                        @error('bank_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Flutterwave/Paystack bank code for your bank.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Account number</label>
                        <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" required>
                        @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Account name</label>
                        <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror" required>
                        @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="feather-arrow-up-right me-1"></i> Request Withdrawal
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Withdrawal History</h5></div>
            <div class="card-body p-0">
                @if($withdrawals->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Amount</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Bank</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawals as $w)
                            <tr>
                                <td class="fw-bold">₦{{ number_format($w->amountNaira(), 2) }}</td>
                                <td>{{ $w->account_number }} ({{ $w->bank_name }})</td>
                                <td>
                                    <span class="badge {{ match($w->status) { 'completed' => 'bg-success', 'processing' => 'bg-info text-white', 'pending' => 'bg-secondary', default => 'bg-danger' } }}">
                                        {{ ucfirst($w->status) }}
                                    </span>
                                    @if($w->status === 'failed' && $w->failure_reason)
                                    <div class="text-muted fs-12">{{ $w->failure_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-muted fs-13">{{ $w->created_at->format('d M Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $withdrawals->links() }}</div>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="feather-arrow-up-right mb-2 d-block" style="font-size:36px;"></i>
                    No withdrawals yet.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
