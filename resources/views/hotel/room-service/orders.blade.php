@extends('layouts.hotel')
@section('title', 'Room Service Orders')
@section('page_title', 'Room Service Orders')
@section('breadcrumb')
    <li class="breadcrumb-item active">Room Service</li>
@endsection
@section('page_actions')
    @if(in_array(auth()->user()->role, ['owner','manager']))
    <a href="{{ route('hotel.room-service.items') }}" class="btn btn-outline-primary btn-sm">
        <i class="feather-list me-1"></i> Manage Menu
    </a>
    @endif
@endsection

@section('content')
<div class="d-flex gap-2 mb-3">
    @foreach(['all','pending','in_progress','delivered','cancelled'] as $tab)
    <a href="{{ route('hotel.room-service.orders', $tab === 'all' ? [] : ['status' => $tab]) }}"
       class="btn btn-sm {{ $currentStatus === $tab ? 'btn-primary' : 'btn-outline-secondary' }}">
        {{ ucfirst(str_replace('_',' ',$tab)) }}
    </a>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        @if($orders->count())
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="fs-11 text-uppercase text-muted fw-semibold">Booking</th>
                    <th class="fs-11 text-uppercase text-muted fw-semibold">Item</th>
                    <th class="fs-11 text-uppercase text-muted fw-semibold">Qty</th>
                    <th class="fs-11 text-uppercase text-muted fw-semibold">Total</th>
                    <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                    <th class="fs-11 text-uppercase text-muted fw-semibold">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('hotel.bookings.show', $order->booking_id) }}" class="text-primary fw-semibold">
                            {{ $order->booking->booking_reference }}
                        </a>
                        <div class="text-muted fs-12">Room {{ $order->booking->room->room_number }} · {{ $order->booking->guest->name }}</div>
                    </td>
                    <td>{{ $order->item->name }}</td>
                    <td>{{ $order->quantity }}</td>
                    <td class="fw-bold">₦{{ number_format($order->totalPriceNaira(), 2) }}</td>
                    <td>
                        <span class="badge {{ match($order->status) { 'pending' => 'bg-secondary', 'in_progress' => 'bg-warning text-dark', 'delivered' => 'bg-success', default => 'bg-danger' } }} text-capitalize">
                            {{ str_replace('_',' ',$order->status) }}
                        </span>
                    </td>
                    <td>
                        <form action="{{ route('hotel.room-service.orders.update', $order->id) }}" method="POST" class="d-flex gap-1">
                            @csrf
                            <select name="status" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                                @foreach(['pending','in_progress','delivered','cancelled'] as $s)
                                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $orders->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-coffee mb-2 d-block" style="font-size:36px;"></i>
            No room service orders yet.
        </div>
        @endif
    </div>
</div>
@endsection
