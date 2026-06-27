@extends('layouts.hotel')
@section('title', 'Outstanding Balances')
@section('page_title', 'Outstanding Balances')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Outstanding Balances</li>
@endsection
@section('page_actions')
    <a href="{{ route('hotel.reports.export.csv', 'outstanding-balances') }}" class="btn btn-sm btn-outline-secondary me-1"><i class="feather-download me-1"></i> CSV</a>
    <a href="{{ route('hotel.reports.export.pdf', 'outstanding-balances') }}" class="btn btn-sm btn-outline-secondary"><i class="feather-file-text me-1"></i> PDF</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Booking</th><th>Guest</th><th>Room</th><th>Total</th><th>Paid</th><th>Balance</th></tr></thead>
            <tbody>
                @foreach($bookings as $b)
                <tr>
                    <td><a href="{{ route('hotel.bookings.show', $b->id) }}" class="text-primary fw-semibold">{{ $b->booking_reference }}</a></td>
                    <td>{{ $b->guest->name }}</td>
                    <td>Room {{ $b->room->room_number }}</td>
                    <td>₦{{ number_format($b->totalAmountNaira(), 2) }}</td>
                    <td>₦{{ number_format($b->amountPaidNaira(), 2) }}</td>
                    <td class="fw-bold text-danger">₦{{ number_format($b->balanceNaira(), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($bookings->isEmpty())<p class="text-muted text-center py-5 mb-0">No outstanding balances.</p>@endif
    </div>
</div>
@endsection
