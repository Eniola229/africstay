<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Confirmation — {{ $hotel->name }}</title>
    <meta name="robots" content="noindex">
    
    {{-- Favicon — use hotel logo if available --}}
    @if($hotel->logo)
        <link rel="icon" href="{{ $hotel->logo }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ $hotel->logo }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @endif
    
    <link rel="stylesheet" href="{{ asset('ashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ashboard/assets/css/africstay-theme.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    @php 
        $brand = (in_array($hotel->tier, ['pro','enterprise']) && $hotel->brand_primary_color) ? $hotel->brand_primary_color : '#0a3622';
        $isConfirmed = $booking->status === 'confirmed' || $booking->status === 'checked_in';
        $isPending = $booking->status === 'pending';
    @endphp

    <style>
        :root {
            --brand: {{ $brand }};
            --brand-dark: #0a3622;
            --gold: #C9A84C;
            --dark: #0f1c2e;
            --muted: #6a8a6a;
            --green: #0a3622;
            --green-bg: #e8f5e8;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(160deg, #0a3622 0%, #1a5a3a 60%, #e8ece8 60%);
            color: var(--dark);
        }

        .top-band {
            padding: 24px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .top-band .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .top-band .brand img { height: 32px; border-radius: 6px; }
        .top-band .brand span { color: #fff; font-weight: 600; font-size: 16px; }
        .top-band .back-link {
            color: rgba(255,255,255,.6);
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .top-band .back-link:hover { color: #fff; }

        .confirm-wrap {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }

        .success-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 32px 0 24px;
            text-align: center;
        }
        .check-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            border: 4px solid #fff;
        }
        .check-circle.confirmed {
            background: var(--green-bg);
            box-shadow: 0 4px 20px rgba(10,54,34,.2);
        }
        .check-circle.pending {
            background: #fff3cd;
            box-shadow: 0 4px 20px rgba(212,160,23,.2);
        }
        .check-circle i { font-size: 30px; }
        .check-circle.confirmed i { color: var(--green); }
        .check-circle.pending i { color: #d4a017; }

        .success-badge h2 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #fff;
            margin: 0 0 6px;
        }
        .success-badge p {
            color: rgba(255,255,255,.65);
            font-size: 14px;
            margin: 0;
        }
        .success-badge .status-pending {
            background: rgba(212,160,23,.2);
            color: #d4a017;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }

        .confirm-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(10,54,34,.18);
        }

        .ref-header {
            background: var(--brand-dark);
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ref-header .ref-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255,255,255,.5);
            margin-bottom: 4px;
        }
        .ref-header .ref-code {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: #8aba8a;
            letter-spacing: 1px;
        }
        .ref-header .ref-status {
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
        }
        .ref-header .ref-status.confirmed {
            background: var(--green-bg);
            color: var(--green);
        }
        .ref-header .ref-status.pending {
            background: #fff3cd;
            color: #d4a017;
        }

        .detail-section {
            padding: 24px 28px;
        }
        .detail-section + .detail-section {
            border-top: 1px solid #e8ece8;
        }
        .detail-section-title {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 7px 0;
            font-size: 14px;
        }
        .detail-row:not(:last-child) {
            border-bottom: 1px solid #f0f2f0;
        }
        .detail-label { color: var(--muted); flex-shrink: 0; margin-right: 16px; }
        .detail-value { font-weight: 500; text-align: right; color: var(--brand-dark); }

        .amounts-row {
            display: flex;
            gap: 12px;
            padding: 20px 28px;
            background: var(--surface);
        }
        .amount-box {
            flex: 1;
            text-align: center;
            padding: 14px 10px;
            background: #fff;
            border-radius: 10px;
            border: 1.5px solid #e8ece8;
        }
        .amount-box .a-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .amount-box .a-val {
            font-weight: 700;
            font-size: 16px;
            color: var(--brand-dark);
        }
        .amount-box.paid .a-val { color: var(--green); }
        .amount-box.balance .a-val { color: {{ $booking->balanceNaira() > 0 ? '#c0392b' : 'var(--green)' }}; }

        .card-footer-note {
            padding: 18px 28px;
            background: #f5faf5;
            border-top: 1px solid #d4e8d4;
            font-size: 13px;
            color: var(--brand-dark);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .card-footer-note i { flex-shrink: 0; margin-top: 1px; }

        .contact-bar {
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 16px rgba(10,54,34,.07);
        }
        .contact-bar p { margin: 0; font-size: 14px; color: var(--muted); }
        .contact-bar a {
            color: var(--brand);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .action-row {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }
        .btn-outline-dark-custom {
            flex: 1;
            padding: 11px;
            border: 1.5px solid #d0d5d0;
            background: #fff;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            cursor: pointer;
            text-align: center;
            transition: background .15s;
            text-decoration: none;
        }
        .btn-outline-dark-custom:hover { background: #f5f6f5; }

        /* Pending spinner */
        .pending-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #d4a017;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .checking-text {
            color: #d4a017;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
        }

        @media print {
            body { background: #fff; }
            .top-band, .contact-bar, .action-row, .card-footer-note, .checking-text { display: none; }
            .confirm-card { box-shadow: none; }
            .success-badge h2, .success-badge p { color: var(--dark); }
        }

        @media (max-width: 540px) {
            .top-band { padding: 16px 20px; }
            .ref-header { flex-direction: column; gap: 10px; }
            .amounts-row { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="top-band">
    <a class="brand" href="{{ route('public.hotel.show', $hotel->slug) }}">
        @if($hotel->logo)<img src="{{ $hotel->logo }}" alt="{{ $hotel->name }}">@endif
        <span>{{ $hotel->name }}</span>
    </a>
    <a class="back-link" href="{{ route('public.hotel.show', $hotel->slug) }}">
        <i class="feather-arrow-left" style="font-size:13px;"></i> Back to hotel
    </a>
</div>

<div class="confirm-wrap">

    <div class="success-badge">
        <div class="check-circle {{ $isConfirmed ? 'confirmed' : 'pending' }}">
            <i class="{{ $isConfirmed ? 'feather-check' : 'feather-clock' }}"></i>
        </div>
        <h2>{{ $isConfirmed ? 'Booking Confirmed!' : 'Payment Processing' }}</h2>
        <p>{{ $isConfirmed ? 'Your reservation at ' . $hotel->name . ' is secured.' : 'We\'re confirming your payment...' }}</p>
        @if($isPending)
            <div class="status-pending">
                <span class="pending-spinner"></span> Awaiting payment confirmation
            </div>
        @endif
    </div>

    <div class="confirm-card">

        <div class="ref-header">
            <div>
                <div class="ref-label">Booking Reference</div>
                <div class="ref-code">{{ $booking->booking_reference }}</div>
            </div>
            <div class="ref-status {{ $isConfirmed ? 'confirmed' : 'pending' }}">
                {{ $isConfirmed ? '✓ Confirmed' : '⏳ Pending' }}
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title">Stay Details</div>
            <div class="detail-row">
                <span class="detail-label">Hotel</span>
                <span class="detail-value">{{ $hotel->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Room</span>
                <span class="detail-value">Room {{ $booking->room->room_number }} — {{ ucfirst($booking->room->type) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check-in</span>
                <span class="detail-value">{{ $booking->check_in->format('D, d M Y') }}
                    @if($booking->check_in->format('H:i') !== '00:00')
                    <span style="color:var(--muted);font-weight:400;"> at {{ $booking->check_in->format('H:i') }}</span>
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check-out</span>
                <span class="detail-value">{{ $booking->check_out->format('D, d M Y') }}
                    @if($booking->check_out->format('H:i') !== '00:00')
                    <span style="color:var(--muted);font-weight:400;"> at {{ $booking->check_out->format('H:i') }}</span>
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Duration</span>
                <span class="detail-value">{{ $booking->nights }} {{ $booking->pricingUnitLabel() }}</span>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title">Guest</div>
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ $booking->guest->name }}</span>
            </div>
            @if($booking->guest->phone)
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ $booking->guest->phone }}</span>
            </div>
            @endif
            @if($booking->guest->email)
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value">{{ $booking->guest->email }}</span>
            </div>
            @endif
        </div>

        <div class="amounts-row">
            <div class="amount-box">
                <div class="a-label">Total</div>
                <div class="a-val">₦{{ number_format($booking->totalAmountNaira(), 0) }}</div>
            </div>
            <div class="amount-box paid">
                <div class="a-label">Amount Paid</div>
                <div class="a-val">₦{{ number_format($booking->amountPaidNaira(), 0) }}</div>
            </div>
            <div class="amount-box balance">
                <div class="a-label">Balance Due</div>
                <div class="a-val">
                    @if($booking->balanceNaira() > 0)
                        ₦{{ number_format($booking->balanceNaira(), 0) }}
                    @else
                        Fully Paid ✓
                    @endif
                </div>
            </div>
        </div>

        <div class="card-footer-note">
            <i class="feather-info"></i>
            <span>Keep your booking reference — <strong>{{ $booking->booking_reference }}</strong> — safe. You'll need it at check-in. {{ $booking->balanceNaira() > 0 ? 'The remaining balance is payable at the hotel.' : '' }}</span>
        </div>

    </div>

    {{-- Pending status check --}}
    @if($isPending)
    <div id="paymentStatusCheck" class="checking-text">
        <span class="pending-spinner"></span>
        <span id="statusMessage">Checking payment status...</span>
    </div>
    @endif

    <div class="contact-bar">
        <p>Questions about your booking?</p>
        <a href="tel:{{ $hotel->phone }}">
            <i class="feather-phone" style="font-size:14px;"></i> Call {{ $hotel->name }}
        </a>
        @if($hotel->email)
        <a href="mailto:{{ $hotel->email }}">
            <i class="feather-mail" style="font-size:14px;"></i> {{ $hotel->email }}
        </a>
        @endif
    </div>

    <div class="action-row">
        <button onclick="window.print()" class="btn-outline-dark-custom">
            <i class="feather-printer" style="font-size:13px;margin-right:5px;"></i> Print / Save PDF
        </button>
        <a href="{{ route('public.hotel.show', $hotel->slug) }}" class="btn-outline-dark-custom">
            <i class="feather-home" style="font-size:13px;margin-right:5px;"></i> Back to Hotel
        </a>
    </div>

</div>

@if($isPending)
<script>
(function() {
    const bookingRef = '{{ $booking->booking_reference }}';
    const hotelSlug = '{{ $hotel->slug }}';
    let checkCount = 0;
    const maxChecks = 24; // 2 minutes (24 * 5 seconds)
    const checkUrl = '{{ route("public.booking.check-status", [$hotel->slug, $booking->booking_reference]) }}';

    function checkStatus() {
        checkCount++;
        
        fetch(checkUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const messageEl = document.getElementById('statusMessage');
            
            if (data.payment_confirmed || data.status === 'confirmed' || data.status === 'checked_in') {
                // Payment confirmed - reload the page to show receipt
                messageEl.textContent = 'Payment confirmed! Redirecting...';
                window.location.reload();
                return;
            }

            if (data.status === 'cancelled') {
                messageEl.textContent = 'Booking was cancelled. Please try again.';
                setTimeout(() => {
                    window.location.href = '{{ route("public.hotel.show", $hotel->slug) }}';
                }, 3000);
                return;
            }

            // Still pending - update message with attempt count
            const attemptsLeft = maxChecks - checkCount;
            messageEl.textContent = `Checking payment status... (${attemptsLeft} attempts remaining)`;

            // Continue checking
            if (checkCount < maxChecks) {
                setTimeout(checkStatus, 5000); // Check every 5 seconds
            } else {
                messageEl.textContent = '⏰ Payment is taking longer than expected. Please contact the hotel directly with your booking reference.';
                document.getElementById('paymentStatusCheck').style.color = '#c0392b';
            }
        })
        .catch(error => {
            console.error('Error checking payment status:', error);
            // Retry after 10 seconds on error
            if (checkCount < maxChecks) {
                setTimeout(checkStatus, 10000);
            }
        });
    }

    // Start checking after 3 seconds
    setTimeout(checkStatus, 3000);
})();
</script>
@endif
</body>
</html>