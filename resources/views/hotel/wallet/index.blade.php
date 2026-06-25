@extends('layouts.hotel')
@section('title', 'Wallet')
@section('page_title', 'Wallet')
@section('breadcrumb')
    <li class="breadcrumb-item active">Wallet</li>
@endsection
@section('page_actions')
    @if($canWithdraw)
    <a href="{{ route('hotel.wallet.withdrawals') }}" class="btn btn-primary btn-sm">
        <i class="feather-arrow-up-right me-1"></i> Withdraw
    </a>
    @endif
@endsection

@section('content')

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card" style="background:#D5F5E3;">
            <div class="card-body">
                <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Wallet Balance</p>
                <h2 class="fw-bold mb-0" style="color:#1E8449;">₦{{ number_format($hotel->walletBalanceNaira(), 2) }}</h2>
                <p class="fs-12 text-muted mt-2 mb-0">Transaction fee: {{ \App\Models\Hotel::TIER_TRANSACTION_FEE_PERCENT[$hotel->tier] }}% per payment ({{ ucfirst($hotel->tier) }} tier)</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Confirmed Payments</h5></div>
    <div class="card-body p-0">
        @if($payments->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Booking</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Guest</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Amount</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Provider</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr>
                        <td><a href="{{ route('hotel.bookings.show', $payment->booking_id) }}" class="text-primary fw-semibold">{{ $payment->booking->booking_reference }}</a></td>
                        <td>{{ $payment->booking->guest->name }}</td>
                        <td class="fw-bold">₦{{ number_format($payment->amountNaira(), 2) }}</td>
                        <td class="text-capitalize">{{ $payment->provider }}</td>
                        <td class="text-muted fs-13">{{ $payment->paid_at?->format('d M Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $payments->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-credit-card mb-2 d-block" style="font-size:36px;"></i>
            No confirmed payments yet.
        </div>
        @endif
    </div>
</div>

@if($withdrawalHistory->count())
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between">
        <h5 class="card-title mb-0">Recent Withdrawals</h5>
        <a href="{{ route('hotel.wallet.withdrawals') }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <tbody>
                @foreach($withdrawalHistory as $w)
                <tr>
                    <td>₦{{ number_format($w->amountNaira(), 2) }}</td>
                    <td>{{ $w->account_number }} ({{ $w->bank_name }})</td>
                    <td>
                        <span class="badge {{ match($w->status) { 'completed' => 'bg-success', 'processing' => 'bg-info text-white', 'pending' => 'bg-secondary', default => 'bg-danger' } }}">
                            {{ ucfirst($w->status) }}
                        </span>
                    </td>
                    <td class="text-muted fs-13">{{ $w->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
