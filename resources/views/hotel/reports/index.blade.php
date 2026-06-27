@extends('layouts.hotel')
@section('title', 'Reports')
@section('page_title', 'Reports')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12"><h6 class="text-muted text-uppercase fs-12 fw-bold">Operational</h6></div>
    @foreach([
        ['hotel.reports.arrivals-departures', 'Daily Arrivals & Departures', 'feather-log-in'],
        ['hotel.reports.occupied-rooms', 'Currently Occupied Rooms', 'feather-home'],
        ['hotel.reports.outstanding-balances', 'Outstanding Balances', 'feather-alert-circle'],
        ['hotel.reports.housekeeping-status', 'Housekeeping Status Board', 'feather-clipboard'],
        ['hotel.reports.room-service-orders', 'Room Service Orders (Pending/In-Progress)', 'feather-coffee'],
    ] as [$route, $label, $icon])
    <div class="col-md-4">
        <a href="{{ route($route) }}" class="card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-text avatar-md rounded bg-light"><i class="{{ $icon }}"></i></div>
                <span class="fw-semibold text-dark">{{ $label }}</span>
            </div>
        </a>
    </div>
    @endforeach

    <div class="col-12 mt-3"><h6 class="text-muted text-uppercase fs-12 fw-bold">Financial</h6></div>
    @foreach([
        ['hotel.reports.revenue-breakdown', 'Revenue Breakdown', 'feather-pie-chart'],
        ['hotel.reports.payments-by-method', 'Payments by Method', 'feather-credit-card'],
        ['hotel.reports.transaction-fees', 'Transaction Fee Deductions', 'feather-percent'],
        ['hotel.reports.wallet-history', 'Wallet Balance History', 'feather-dollar-sign'],
        ['hotel.reports.withdrawal-history', 'Withdrawal History', 'feather-arrow-up-right'],
        ['hotel.reports.profit-and-loss', 'Monthly P&L Summary', 'feather-bar-chart-2'],
    ] as [$route, $label, $icon])
    <div class="col-md-4">
        <a href="{{ route($route) }}" class="card text-decoration-none h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-text avatar-md rounded bg-light"><i class="{{ $icon }}"></i></div>
                <span class="fw-semibold text-dark">{{ $label }}</span>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endsection
