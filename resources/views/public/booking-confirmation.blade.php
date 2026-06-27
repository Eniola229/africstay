<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Confirmed — {{ $hotel->name }}</title>
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/africstay-theme.css') }}">
</head>
<body style="background:#f4f6f8;">
<div class="container py-5" style="max-width:600px;">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
             style="width:70px;height:70px;background:#D5F5E3;color:#1E8449;">
            <i class="feather-check" style="font-size:32px;"></i>
        </div>
        <h3 class="fw-bold">Booking Confirmed!</h3>
        <p class="text-muted">Screenshot or save this page — you'll need your reference at check-in.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="text-center mb-3">
                <span class="text-muted fs-13">Booking Reference</span>
                <h4 class="fw-bold">{{ $booking->booking_reference }}</h4>
            </div>
            <hr>
            <div class="row mb-2"><div class="col-5 text-muted">Hotel</div><div class="col-7 fw-semibold">{{ $hotel->name }}</div></div>
            <div class="row mb-2"><div class="col-5 text-muted">Guest</div><div class="col-7">{{ $booking->guest->name }}</div></div>
            <div class="row mb-2"><div class="col-5 text-muted">Room</div><div class="col-7">Room {{ $booking->room->room_number }} ({{ ucfirst($booking->room->type) }})</div></div>
            <div class="row mb-2"><div class="col-5 text-muted">Dates</div><div class="col-7">{{ $booking->check_in->format('d M Y') }} – {{ $booking->check_out->format('d M Y') }}</div></div>
            <div class="row mb-2"><div class="col-5 text-muted">Total</div><div class="col-7 fw-bold">₦{{ number_format($booking->totalAmountNaira(), 2) }}</div></div>
            <div class="row mb-2"><div class="col-5 text-muted">Deposit Paid</div><div class="col-7 fw-bold text-success">₦{{ number_format($booking->amountPaidNaira(), 2) }}</div></div>
            <div class="row"><div class="col-5 text-muted">Balance Due</div><div class="col-7 fw-bold">₦{{ number_format($booking->balanceNaira(), 2) }}</div></div>
        </div>
    </div>

    <div class="text-center mt-4 text-muted fs-13">
        <p><i class="feather-phone me-1"></i> Questions? Call {{ $hotel->name }} on {{ $hotel->phone }}</p>
    </div>
</div>
</body>
</html>
