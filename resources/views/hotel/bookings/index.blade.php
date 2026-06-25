@extends('layouts.hotel')
@section('title', 'Bookings')
@section('page_title', 'Bookings')
@section('breadcrumb')
    <li class="breadcrumb-item active">Bookings</li>
@endsection
@section('page_actions')
    <a href="{{ route('hotel.bookings.create') }}" class="btn btn-primary btn-sm">
        <i class="feather-plus me-1"></i> New Walk-in Booking
    </a>
@endsection

@section('content')

<div class="d-flex gap-2 mb-3 flex-wrap">
    @foreach(['all','pending','confirmed','checked_in','checked_out','cancelled'] as $tab)
    <a href="{{ route('hotel.bookings.index', $tab === 'all' ? [] : ['status' => $tab]) }}"
       class="btn btn-sm {{ $currentStatus === $tab ? 'btn-primary' : 'btn-outline-secondary' }}">
        {{ ucfirst(str_replace('_', ' ', $tab)) }}
    </a>
    @endforeach

    <form method="GET" class="ms-auto d-flex" style="max-width:280px;">
        @if($currentStatus !== 'all')<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search ref, guest, phone...">
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($bookings->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Reference</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Guest</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Room</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Dates</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Balance</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                    @php
                        $badge = match($booking->status) {
                            'pending' => 'bg-secondary',
                            'confirmed' => 'bg-info text-white',
                            'checked_in' => 'bg-success',
                            'checked_out' => 'bg-dark',
                            'cancelled' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td><a href="{{ route('hotel.bookings.show', $booking->id) }}" class="fw-semibold text-primary">{{ $booking->booking_reference }}</a></td>
                        <td>{{ $booking->guest->name }}<div class="text-muted fs-12">{{ $booking->guest->phone ?? $booking->guest->email ?? '—' }}</div></td>
                        <td>Room {{ $booking->room->room_number }}</td>
                        <td class="fs-13">{{ $booking->check_in->format('d M') }} – {{ $booking->check_out->format('d M Y') }}</td>
                        <td class="fw-bold">₦{{ number_format($booking->balanceNaira(), 2) }}</td>
                        <td><span class="badge {{ $badge }} text-capitalize">{{ str_replace('_',' ',$booking->status) }}</span></td>
                        <td><a href="{{ route('hotel.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $bookings->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-calendar mb-2 d-block" style="font-size:40px;"></i>
            <p class="mb-3">No bookings found.</p>
            <a href="{{ route('hotel.bookings.create') }}" class="btn btn-primary">Create a booking</a>
        </div>
        @endif
    </div>
</div>
@endsection
