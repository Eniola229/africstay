@extends('layouts.hotel')
@section('title', 'Revenue Breakdown')
@section('page_title', 'Revenue Breakdown')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Revenue Breakdown</li>
@endsection

@section('content')
@include('hotel.reports.partials.date-filter')

<div class="row g-3">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Room Charges</p>
            <h3 class="fw-bold">₦{{ number_format(max(0,$roomRevenue) / 100, 2) }}</h3>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Room Service &amp; Extras</p>
            <h3 class="fw-bold">₦{{ number_format($extrasRevenue / 100, 2) }}</h3>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card" style="background:#D5F5E3;"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Total Confirmed Payments</p>
            <h3 class="fw-bold" style="color:#1E8449;">₦{{ number_format($totalConfirmedPayments / 100, 2) }}</h3>
        </div></div>
    </div>
</div>
@endsection
