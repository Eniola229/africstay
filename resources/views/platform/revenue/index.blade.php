@extends('layouts.platform.app')
@section('title', 'Revenue Reports')
@section('page_title', 'Platform Revenue Reports')

@section('content')
<form method="GET" class="d-flex gap-2 mb-3 align-items-end">
    <div><label class="form-label fs-12 fw-bold mb-1">From</label><input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}" class="form-control form-control-sm"></div>
    <div><label class="form-label fs-12 fw-bold mb-1">To</label><input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}" class="form-control form-control-sm"></div>
    <button class="btn btn-sm btn-dark">Filter</button>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Subscription Revenue</p>
            <h4 class="fw-bold">₦{{ number_format($subscriptionRevenue / 100, 2) }}</h4>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Transaction Fee Revenue</p>
            <h4 class="fw-bold">₦{{ number_format($transactionFeeRevenue / 100, 2) }}</h4>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card" style="background:#D5F5E3;"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">MRR</p>
            <h4 class="fw-bold" style="color:#1E8449;">₦{{ number_format($mrr / 100, 2) }}</h4>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Active Subscriptions</p>
            <h4 class="fw-bold">{{ $totalActiveSubscriptions }}</h4>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Top Hotels by Fees Generated</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($feesByHotel as $row)
                        <tr><td>{{ $row['hotel']->name }}</td><td class="fw-bold">₦{{ number_format($row['fee'] / 100, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                @if(empty($feesByHotel))<p class="text-muted text-center py-3 mb-0">No fee revenue in this range.</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Hotels by Tier</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($byTier as $tier => $count)
                        <tr><td class="text-capitalize">{{ $tier }}</td><td class="fw-bold">{{ $count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Churn (last 90 days)</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($churned as $hotel)
                        <tr><td>{{ $hotel->name }}</td><td><span class="badge bg-danger">{{ $hotel->subscription_status }}</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
                @if($churned->isEmpty())<p class="text-muted text-center py-3 mb-0">No churn in the last 90 days.</p>@endif
            </div>
        </div>
    </div>
</div>
@endsection
