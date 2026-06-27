@extends('layouts.hotel')
@section('title', 'Room Service Orders')
@section('page_title', 'Room Service Orders (Pending/In-Progress)')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Room Service Orders</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Booking</th><th>Item</th><th>Qty</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($orders as $o)
                <tr>
                    <td><a href="{{ route('hotel.bookings.show', $o->booking_id) }}" class="text-primary fw-semibold">{{ $o->booking->booking_reference }}</a></td>
                    <td>{{ $o->item->name }}</td>
                    <td>{{ $o->quantity }}</td>
                    <td class="fw-bold">₦{{ number_format($o->totalPriceNaira(), 2) }}</td>
                    <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_',' ',$o->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($orders->isEmpty())<p class="text-muted text-center py-5 mb-0">No pending or in-progress orders.</p>@endif
    </div>
</div>
@endsection
