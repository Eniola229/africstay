@extends('layouts.hotel')
@section('title', 'Payments by Method')
@section('page_title', 'Payments by Method')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Payments by Method</li>
@endsection
@section('page_actions')
    <a href="{{ route('hotel.reports.export.csv', 'payments-by-method') }}" class="btn btn-sm btn-outline-secondary"><i class="feather-download me-1"></i> CSV</a>
@endsection

@section('content')
@include('hotel.reports.partials.date-filter')

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Method</th><th>Count</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($breakdown as $row)
                <tr>
                    <td class="text-capitalize">{{ str_replace('_',' ',$row->payment_method) }}</td>
                    <td>{{ $row->count }}</td>
                    <td class="fw-bold">₦{{ number_format($row->total / 100, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($breakdown->isEmpty())<p class="text-muted text-center py-5 mb-0">No confirmed payments in this range.</p>@endif
    </div>
</div>
@endsection
