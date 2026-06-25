@extends('layouts.hotel')
@section('title', 'Booking ' . $booking->booking_reference)
@section('page_title', 'Booking ' . $booking->booking_reference)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.bookings.index') }}">Bookings</a></li>
    <li class="breadcrumb-item active">{{ $booking->booking_reference }}</li>
@endsection

@section('content')
@php
    $badge = match($booking->status) {
        'pending' => 'bg-secondary', 'confirmed' => 'bg-info text-white', 'checked_in' => 'bg-success',
        'checked_out' => 'bg-dark', 'cancelled' => 'bg-danger', default => 'bg-secondary',
    };
    $latestPayment = $booking->payments->sortByDesc('created_at')->first();
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Booking Details</h5>
                <span class="badge {{ $badge }} text-capitalize">{{ str_replace('_',' ',$booking->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-2"><div class="col-4 text-muted">Guest</div><div class="col-8 fw-semibold">{{ $booking->guest->name }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Contact</div><div class="col-8">{{ $booking->guest->phone ?? '—' }} {{ $booking->guest->email ? '· '.$booking->guest->email : '' }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Room</div><div class="col-8">Room {{ $booking->room->room_number }} ({{ ucfirst($booking->room->type) }})</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Dates</div><div class="col-8">{{ $booking->check_in->format('d M Y') }} – {{ $booking->check_out->format('d M Y') }} ({{ $booking->nights }} night(s))</div></div>
                @if($booking->notes)
                <div class="row mb-2"><div class="col-4 text-muted">Notes</div><div class="col-8">{{ $booking->notes }}</div></div>
                @endif
            </div>
        </div>

        @if($booking->status === 'confirmed')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Check In</h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.bookings.check-in', $booking->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">ID type</label>
                            <select name="id_type" class="form-select">
                                <option value="nin">NIN</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">ID number</label>
                            <input type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror" required>
                            @error('id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="feather-log-in me-1"></i> Confirm Check-In
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if($booking->status === 'checked_in')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Check Out</h5></div>
            <div class="card-body">
                @if($booking->balance > 0)
                <div class="alert alert-warning">
                    Outstanding balance: <strong>₦{{ number_format($booking->balanceNaira(), 2) }}</strong>.
                    Guest must pay, or a manager/owner can override below.
                </div>
                <form action="{{ route('hotel.bookings.check-out', $booking->id) }}" method="POST" class="mb-2">
                    @csrf
                    <input type="hidden" name="manager_override" value="1">
                    <div class="mb-2">
                        <input type="text" name="override_reason" class="form-control @error('override_reason') is-invalid @enderror" placeholder="Reason for override (manager/owner only)">
                        @error('override_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="feather-alert-triangle me-1"></i> Override &amp; Check Out
                    </button>
                </form>
                @else
                <form action="{{ route('hotel.bookings.check-out', $booking->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-dark">
                        <i class="feather-log-out me-1"></i> Confirm Check-Out
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endif

        @if($booking->status === 'checked_out')
        <div class="card mb-3 border-success">
            <div class="card-header bg-success text-white"><h5 class="card-title mb-0">Receipt</h5></div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ $booking->hotel->name }}</strong> · {{ $booking->hotel->phone }}</p>
                <p class="mb-1">Booking ref: <strong>{{ $booking->booking_reference }}</strong></p>
                <p class="mb-1">Guest: {{ $booking->guest->name }} · Room {{ $booking->room->room_number }}</p>
                <p class="mb-1">{{ $booking->check_in->format('d M Y') }} – {{ $booking->check_out->format('d M Y') }}</p>
                <hr>
                <p class="mb-1">Total: ₦{{ number_format($booking->totalAmountNaira(), 2) }}</p>
                <p class="mb-1">Paid: ₦{{ number_format($booking->amountPaidNaira(), 2) }}</p>
                <p class="mb-0 fw-bold">Balance: ₦{{ number_format($booking->balanceNaira(), 2) }}</p>
                <button onclick="window.print()" class="btn btn-sm btn-outline-dark mt-3">
                    <i class="feather-printer me-1"></i> Print Receipt
                </button>
            </div>
        </div>
        @endif

        @if($booking->status !== 'cancelled' && $booking->status !== 'checked_out')
        <form action="{{ route('hotel.bookings.cancel', $booking->id) }}" method="POST"
              onsubmit="return confirm('Cancel booking {{ $booking->booking_reference }}?');">
            @csrf
            <button type="submit" class="btn btn-link text-danger">Cancel this booking</button>
        </form>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Payment Summary</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Total</span><span class="fw-bold">₦{{ number_format($booking->totalAmountNaira(), 2) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Paid</span><span class="fw-bold text-success">₦{{ number_format($booking->amountPaidNaira(), 2) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between"><span class="fw-bold">Balance</span><span class="fw-bold {{ $booking->balance > 0 ? 'text-danger' : 'text-success' }}">₦{{ number_format($booking->balanceNaira(), 2) }}</span></div>
            </div>
        </div>

        @if($latestPayment)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Payment Account</h5></div>
            <div class="card-body">
                <p class="text-muted fs-13 mb-1">Show or read this out to the guest:</p>
                <div class="p-3 rounded mb-2" style="background:#f8f9fa;">
                    <div class="fs-12 text-muted">Account Number</div>
                    <div class="fw-bold fs-5">{{ $latestPayment->virtual_account_number }}</div>
                    <div class="fs-12 text-muted mt-2">Bank</div>
                    <div class="fw-semibold">{{ $latestPayment->virtual_account_bank }}</div>
                    <div class="fs-12 text-muted mt-2">Account Name</div>
                    <div class="fw-semibold">{{ $latestPayment->virtual_account_name }}</div>
                    <div class="fs-12 text-muted mt-2">Amount Due</div>
                    <div class="fw-bold">₦{{ number_format($latestPayment->amountNaira(), 2) }}</div>
                </div>
                <span class="badge {{ $latestPayment->status === 'confirmed' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ ucfirst($latestPayment->status) }}
                </span>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
