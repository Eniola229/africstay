@extends('layouts.platform.app')
@section('title', 'Revenue Reports')
@section('page_title', 'Platform Revenue Reports')
@section('breadcrumb')
    <li class="breadcrumb-item active">Revenue Reports</li>
@endsection

@section('content')

{{-- ── Date Filter ──────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('platform.revenue.index') }}"
              class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label fw-semibold fs-13 mb-0">From</label>
                <input type="date" name="from"
                       value="{{ request('from', $from->format('Y-m-d')) }}"
                       class="form-control form-control-sm" style="max-width:160px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label fw-semibold fs-13 mb-0">To</label>
                <input type="date" name="to"
                       value="{{ request('to', $to->format('Y-m-d')) }}"
                       class="form-control form-control-sm" style="max-width:160px;">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="feather-filter me-1"></i> Apply Filter
            </button>
            <span class="text-muted fs-12">
                Showing {{ $periodDays }} day{{ $periodDays > 1 ? 's' : '' }}:
                {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
            </span>
        </form>
    </div>
</div>

{{-- ── Top Stats ────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Subscription Revenue</p>
                        <h2 class="fw-bold mb-0 text-success">₦{{ number_format($subscriptionRevenue / 100, 2) }}</h2>
                        @php $subChange = $prevSubscriptionRevenue > 0 ? round((($subscriptionRevenue - $prevSubscriptionRevenue) / $prevSubscriptionRevenue) * 100, 1) : null; @endphp
                        @if($subChange !== null)
                        <small class="{{ $subChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="feather-{{ $subChange >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($subChange) }}% vs previous period
                        </small>
                        @else
                        <small class="text-muted">in selected period</small>
                        @endif
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;flex-shrink:0;">
                        <i class="feather-credit-card"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Transaction Fee Revenue</p>
                        <h2 class="fw-bold mb-0">₦{{ number_format($transactionFeeRevenue / 100, 2) }}</h2>
                        @php $txChange = $prevTransactionFeeRevenue > 0 ? round((($transactionFeeRevenue - $prevTransactionFeeRevenue) / $prevTransactionFeeRevenue) * 100, 1) : null; @endphp
                        @if($txChange !== null)
                        <small class="{{ $txChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="feather-{{ $txChange >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($txChange) }}% vs previous period
                        </small>
                        @else
                        <small class="text-muted">per-transaction cuts</small>
                        @endif
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-percent"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100" style="background:#D5F5E3;">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="fw-semibold text-uppercase mb-1" style="font-size:12px;color:#1A5276;">MRR</p>
                        <h2 class="fw-bold mb-0" style="color:#1A5276;">₦{{ number_format($mrr / 100, 2) }}</h2>
                        <small style="color:#1E8449;">based on active subs</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded flex-shrink-0" style="background:#A9DFBF;color:#1A5276;">
                        <i class="feather-trending-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Active Subscriptions</p>
                        <h2 class="fw-bold mb-0 text-primary">{{ number_format($totalActiveSubscriptions) }}</h2>
                        <small class="text-muted">currently paying hotels</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Secondary stats: Payments + Withdrawals ────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Confirmed Payments</p>
                <h2 class="fw-bold mb-0 text-success">{{ number_format($subPaymentCounts['confirmed'] ?? 0) }}</h2>
                <small class="text-muted">in period</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Failed Payments</p>
                <h2 class="fw-bold mb-0 text-danger">{{ number_format($subPaymentCounts['failed'] ?? 0) }}</h2>
                <small class="text-muted">in period</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Withdrawals Pending</p>
                <h2 class="fw-bold mb-0 text-warning">₦{{ number_format($withdrawalStats['pending'] / 100, 2) }}</h2>
                <small class="text-muted">queued for payout</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Withdrawals Paid</p>
                <h2 class="fw-bold mb-0 text-success">₦{{ number_format($withdrawalStats['completed'] / 100, 2) }}</h2>
                <small class="text-muted">completed in period</small>
            </div>
        </div>
    </div>
</div>

{{-- ── Detail Tables ────────────────────────────────────────────── --}}
<div class="row">
    <div class="col-lg-6">

        {{-- Top Hotels by Fees --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Top Hotels by Fees Generated</h5>
                <span class="badge bg-secondary">Top 10</span>
            </div>
            <div class="card-body p-0">
                @if(!empty($feesByHotel))
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">#</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Hotel</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Gross Volume</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Fee Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feesByHotel as $i => $row)
                            <tr>
                                <td class="text-muted fs-12">{{ $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('platform.hotels.show', $row['hotel']->id) }}"
                                       class="fw-semibold text-primary text-decoration-none">
                                        {{ $row['hotel']->name }}
                                    </a>
                                    <div class="text-muted fs-12 text-capitalize">{{ $row['hotel']->tier }}</div>
                                </td>
                                <td class="text-muted fs-13">₦{{ number_format($row['gross'] / 100, 2) }}</td>
                                <td class="fw-bold text-success">₦{{ number_format($row['fee'] / 100, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="feather-bar-chart-2 d-block mb-2" style="font-size:32px;"></i>
                    No fee revenue in this period.
                </div>
                @endif
            </div>
        </div>

    </div>
    <div class="col-lg-6">

        {{-- Hotels by Tier --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Hotels by Tier</h5></div>
            <div class="card-body p-0">
                @php
                    $tierConfig = [
                        'starter'    => ['bg-secondary', 'bg-secondary'],
                        'growth'     => ['bg-info',      'bg-info'],
                        'pro'        => ['bg-primary',   'bg-primary'],
                        'enterprise' => ['bg-success',   'bg-success'],
                    ];
                    $tierTotal = $byTier->sum() ?: 1;
                @endphp
                <ul class="list-group list-group-flush">
                    @foreach(['starter','growth','pro','enterprise'] as $t)
                    @php $count = $byTier[$t] ?? 0; $pct = round($count / $tierTotal * 100); @endphp
                    <li class="list-group-item px-3 py-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-semibold text-capitalize">{{ $t }}</span>
                            <span class="fw-bold">{{ $count }}</span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar {{ $tierConfig[$t][0] }}"
                                 style="width:{{ $pct }}%;"></div>
                        </div>
                        <div class="fs-11 text-muted mt-1">{{ $pct }}% of all hotels</div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Churn --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Churn (Last 90 Days)</h5>
                <span class="badge bg-danger">{{ $churned->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($churned->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Hotel</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Tier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($churned as $hotel)
                            <tr>
                                <td>
                                    <a href="{{ route('platform.hotels.show', $hotel->id) }}"
                                       class="fw-semibold text-primary text-decoration-none">
                                        {{ $hotel->name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ ucwords(str_replace('_', ' ', $hotel->subscription_status)) }}
                                    </span>
                                </td>
                                <td class="text-muted fs-13 text-capitalize">{{ $hotel->tier }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="feather-check-circle d-block mb-2" style="font-size:32px;color:#2ECC71;"></i>
                    <p class="mb-0">No churn in the last 90 days — great!</p>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection