@extends('layouts.hotel')
@section('title', 'Wallet History')
@section('page_title', 'Wallet Balance History')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Wallet History</li>
@endsection

@section('content')
@include('hotel.reports.partials.date-filter')

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0 text-success">Credits</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0"><tbody>
                    @foreach($credits as $c)
                    <tr><td>{{ $c->booking->booking_reference ?? '—' }}</td><td class="fw-bold text-success">+₦{{ number_format($c->amountNaira(), 2) }}</td><td class="text-muted fs-13">{{ $c->paid_at?->format('d M Y') }}</td></tr>
                    @endforeach
                </tbody></table>
                @if($credits->isEmpty())<p class="text-muted text-center py-3 mb-0">No credits in this range.</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0 text-danger">Debits (Withdrawals)</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0"><tbody>
                    @foreach($debits as $d)
                    <tr><td>{{ $d->reference }}</td><td class="fw-bold text-danger">-₦{{ number_format($d->amountNaira(), 2) }}</td><td class="text-muted fs-13">{{ $d->created_at->format('d M Y') }}</td></tr>
                    @endforeach
                </tbody></table>
                @if($debits->isEmpty())<p class="text-muted text-center py-3 mb-0">No withdrawals in this range.</p>@endif
            </div>
        </div>
    </div>
</div>
@endsection
