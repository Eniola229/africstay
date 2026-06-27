@extends('layouts.hotel')
@section('title', 'Transaction Fees')
@section('page_title', 'Transaction Fee Deductions')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Transaction Fees</li>
@endsection

@section('content')
@include('hotel.reports.partials.date-filter')

<div class="card">
    <div class="card-body">
        <div class="row mb-3"><div class="col-6 text-muted">Gross payments received</div><div class="col-6 fw-bold text-end">₦{{ number_format($grossTotal / 100, 2) }}</div></div>
        <div class="row mb-3"><div class="col-6 text-muted">AfricStay fee rate ({{ $feePercent }}%)</div><div class="col-6 fw-bold text-end text-danger">-₦{{ number_format($feesDeducted / 100, 2) }}</div></div>
        <hr>
        <div class="row"><div class="col-6 fw-bold">Net credited to wallet</div><div class="col-6 fw-bold text-end text-success">₦{{ number_format($netCredited / 100, 2) }}</div></div>
    </div>
</div>
@endsection
