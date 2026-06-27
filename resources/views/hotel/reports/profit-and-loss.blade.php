@extends('layouts.hotel')
@section('title', 'Monthly P&L Summary')
@section('page_title', 'Monthly P&L Summary')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">P&amp;L Summary</li>
@endsection
@section('page_actions')
    <a href="{{ route('hotel.reports.export.pdf', 'profit-and-loss') }}" class="btn btn-sm btn-outline-secondary"><i class="feather-file-text me-1"></i> PDF</a>
@endsection

@section('content')
@include('hotel.reports.partials.date-filter')

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <div class="row mb-3"><div class="col-7 text-muted">Total revenue</div><div class="col-5 fw-bold text-end">₦{{ number_format($totalRevenue / 100, 2) }}</div></div>
        <div class="row mb-3"><div class="col-7 text-muted">Fees deducted</div><div class="col-5 fw-bold text-end text-danger">-₦{{ number_format($feesDeducted / 100, 2) }}</div></div>
        <div class="row mb-3"><div class="col-7 text-muted">Withdrawals (completed)</div><div class="col-5 fw-bold text-end text-danger">-₦{{ number_format($withdrawalsTotal / 100, 2) }}</div></div>
        <hr>
        <div class="row"><div class="col-7 fw-bold">Closing wallet balance</div><div class="col-5 fw-bold text-end text-success">₦{{ number_format($closingBalance / 100, 2) }}</div></div>
    </div>
</div>
@endsection
