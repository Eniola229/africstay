<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $hotel->meta_title ?? $hotel->name . ' — Book Online | AfricStay' }}</title>
    <meta name="description" content="{{ $hotel->meta_description ?? ($hotel->description ? \Illuminate\Support\Str::limit($hotel->description, 160) : 'Book a room at ' . $hotel->name . ' online.') }}">
    <meta property="og:title" content="{{ $hotel->name }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($hotel->description ?? '', 160) }}">
    @if($hotel->logo)<meta property="og:image" content="{{ $hotel->logo }}">@endif
    <meta property="og:type" content="website">

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

    @php $brand = (in_array($hotel->tier, ['pro','enterprise']) && $hotel->brand_primary_color) ? $hotel->brand_primary_color : '#0a3622'; @endphp

    <style>
        :root {
            --brand: {{ $brand }};
            --brand-light: {{ $brand }}18;
            --brand-dark: #0a3622;
            --gold: #C9A84C;
            --dark: #0f1c2e;
            --muted: #6a8a6a;
            --surface: #f0f5f0;
            --card-shadow: 0 4px 24px rgba(10,54,34,.08);
            --green-bg: #e8f5e8;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--surface);
            color: var(--dark);
            font-size: 15px;
        }

        .pub-nav {
            position: absolute;
            top: 0; left: 0; right: 0;
            z-index: 20;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pub-nav .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .pub-nav .brand img { height: 36px; border-radius: 6px; }
        .pub-nav .brand span {
            color: #fff;
            font-weight: 600;
            font-size: 17px;
            letter-spacing: .3px;
        }
        .pub-nav .contact-pill {
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,.25);
            color: #fff;
            padding: 7px 18px;
            border-radius: 40px;
            font-size: 13px;
            text-decoration: none;
            transition: background .2s;
        }
        .pub-nav .contact-pill:hover { background: rgba(255,255,255,.25); }

        .hero {
            position: relative;
            min-height: 520px;
            display: flex;
            align-items: flex-end;
            padding-bottom: 100px;
            background: var(--dark) center/cover no-repeat;
            @if($hotel->logo)
            background-image: linear-gradient(160deg, rgba(10,54,34,.82) 0%, rgba(10,54,34,.55) 100%), url('{{ $hotel->logo }}');
            @else
            background: linear-gradient(160deg, #0a3622 0%, #1a5a3a 100%);
            @endif
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 80px;
            background: linear-gradient(to bottom, transparent, var(--surface));
        }
        .hero-content {
            position: relative;
            z-index: 2;
            color: #fff;
            padding: 0 40px;
            max-width: 700px;
        }
        .hero-eyebrow {
            display: inline-block;
            background: var(--brand);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 3px;
            margin-bottom: 14px;
        }
        .hero-content h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(32px, 5vw, 52px);
            font-weight: 700;
            line-height: 1.15;
            margin: 0 0 12px;
        }
        .hero-meta {
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 14px;
            color: rgba(255,255,255,.75);
        }
        .hero-meta i { font-size: 13px; margin-right: 4px; }

        .search-bar-wrap {
            position: relative;
            z-index: 10;
            margin-top: -56px;
            padding: 0 40px;
        }
        .search-bar {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 16px 48px rgba(10,54,34,.14);
            padding: 28px 32px;
        }
        .search-bar h5 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: var(--brand-dark);
            margin: 0 0 20px;
        }
        .search-bar .form-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .search-bar .form-control,
        .search-bar .form-select {
            border: 1.5px solid #e4e8ee;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            color: var(--dark);
            transition: border-color .2s, box-shadow .2s;
        }
        .search-bar .form-control:focus,
        .search-bar .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--brand-light);
            outline: none;
        }
        .btn-search {
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 11px 28px;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
            transition: opacity .2s, transform .15s;
            cursor: pointer;
        }
        .btn-search:hover { opacity: .9; transform: translateY(-1px); }
        .btn-search:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            color: var(--brand-dark);
            margin: 0 0 24px;
        }

        .room-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 2px solid transparent;
            transition: border-color .2s, box-shadow .2s, transform .2s;
            display: flex;
            flex-direction: column;
        }
        .room-card:hover { transform: translateY(-3px); box-shadow: 0 8px 32px rgba(10,54,34,.13); }
        .room-card .room-img {
            height: 190px;
            object-fit: cover;
            width: 100%;
        }
        .room-card .room-img-placeholder {
            height: 190px;
            background: linear-gradient(135deg, #e8ece8 0%, #d4d9d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #b0b8b0;
        }
        .room-card .card-body { 
            padding: 18px; 
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .room-card .room-name { font-weight: 700; font-size: 16px; margin: 0 0 4px; color: var(--brand-dark); }
        .room-card .room-price { color: var(--brand); font-weight: 600; font-size: 15px; }
        .room-card .room-meta { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .room-card .room-total { font-size: 13px; color: var(--dark); margin-top: 8px; }
        .room-card .full-payment-badge {
            display: inline-block;
            background: var(--green-bg);
            color: var(--brand-dark);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 6px;
        }
        .btn-book-room {
            display: block;
            width: 100%;
            margin-top: auto;
            padding: 10px;
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: opacity .2s;
        }
        .btn-book-room:hover { opacity: .88; }

        .about-section {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--card-shadow);
        }

        .showcase-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        .showcase-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .showcase-card .card-body { padding: 20px; }
        .type-badge {
            display: inline-block;
            background: var(--brand-light);
            color: var(--brand);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .6px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .pub-footer {
            background: var(--brand-dark);
            color: rgba(255,255,255,.7);
            padding: 40px;
            margin-top: 80px;
        }
        .pub-footer h6 {
            color: #fff;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .pub-footer a { color: #8aba8a; text-decoration: none; }
        .pub-footer .powered {
            font-size: 11px;
            color: rgba(255,255,255,.35);
            margin-top: 28px;
        }

        .modal-content { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header {
            background: var(--brand-dark);
            color: #fff;
            padding: 20px 28px;
            border-bottom: none;
        }
        .modal-header .modal-title { font-family: 'Playfair Display', serif; font-size: 20px; color: #fff; }
        .modal-header .btn-close { filter: invert(1); }
        .modal-body { padding: 28px; }
        .modal-footer { padding: 16px 28px 24px; border-top: 1px solid #eef0f3; }
        .summary-box {
            background: var(--surface);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .summary-box .s-row { display: flex; justify-content: space-between; padding: 5px 0; }
        .summary-box .s-row:not(:last-child) { border-bottom: 1px solid #e8ece8; }
        .summary-box .s-label { color: var(--muted); }
        .summary-box .s-val { font-weight: 600; color: var(--brand-dark); }
        .modal .form-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: var(--muted);
        }
        .modal .form-control {
            border: 1.5px solid #e4e8ee;
            border-radius: 8px;
            font-size: 14px;
            padding: 10px 14px;
        }
        .modal .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--brand-light);
        }
        .btn-pay {
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 13px 36px;
            font-weight: 600;
            font-size: 15px;
            width: 100%;
            cursor: pointer;
            transition: opacity .2s;
        }
        .btn-pay:hover { opacity: .88; }

        .divider { border: none; border-top: 1px solid #e8ece8; margin: 28px 0; }

        @media (max-width: 768px) {
            .pub-nav { padding: 16px 20px; }
            .hero-content { padding: 0 20px; }
            .search-bar-wrap { padding: 0 16px; }
            .search-bar { padding: 20px; }
            .pub-footer { padding: 32px 20px; }
        }
    </style>
</head>
<body>

{{-- NAV --}}
<nav class="pub-nav">
    <a class="brand" href="#">
        @if($hotel->logo)<img src="{{ $hotel->logo }}" alt="{{ $hotel->name }}">@endif
        <span>{{ $hotel->name }}</span>
    </a>
    @if($hotel->phone)
    <a class="contact-pill" href="tel:{{ $hotel->phone }}">
        <i class="feather-phone" style="font-size:12px;margin-right:5px;"></i> {{ $hotel->phone }}
    </a>
    @endif
</nav>

{{-- HERO --}}
<div class="hero">
    <div class="hero-content">
        <span class="hero-eyebrow">Official Booking</span>
        <h1>{{ $hotel->name }}</h1>
        <div class="hero-meta">
            @if($hotel->city || $hotel->state)
            <span><i class="feather-map-pin"></i>{{ $hotel->city }}{{ $hotel->city && $hotel->state ? ', ' : '' }}{{ $hotel->state }}</span>
            @endif
            @if($hotel->phone)
            <span><i class="feather-phone"></i>{{ $hotel->phone }}</span>
            @endif
        </div>
    </div>
</div>

{{-- BOOKING SEARCH BAR --}}
<div class="search-bar-wrap">
    <div class="search-bar">
        <h5>Check Availability &amp; Book Your Stay</h5>

        @if(session('info'))
        <div class="alert alert-info mb-4">{{ session('info') }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger mb-4">
            @foreach($errors->all() as $error)<p class="mb-0">{{ $error }}</p>@endforeach
        </div>
        @endif

        <form id="availabilityForm" class="row g-3 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label">Check-in</label>
                <input type="datetime-local" id="check_in" class="form-control" required>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label">Check-out</label>
                <input type="datetime-local" id="check_out" class="form-control" required>
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="form-label">Guests</label>
                <input type="number" id="guest_count" class="form-control" value="1" min="1" max="20">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="form-label">Room type</label>
                <select id="room_type" class="form-select">
                    <option value="">All types</option>
                    @foreach($roomTypes->keys() as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-4">
                <button type="submit" class="btn-search" id="searchBtn">
                    <i class="feather-search" style="font-size:14px;margin-right:5px;"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

{{-- RESULTS --}}
<div class="container" style="max-width:1100px; padding:0 40px;">
    <div id="resultsSection" style="display:none;" class="mt-5">
        <p class="section-label">Available Rooms</p>
        <h2 class="section-title" id="resultsTitle">Choose Your Room</h2>
        <div id="searchError" class="alert alert-warning d-none mb-4"></div>
        <div class="row g-3" id="resultsList"></div>
    </div>

    <hr class="divider" style="margin-top:60px;">

    {{-- ABOUT --}}
    @if($hotel->description)
    <div class="about-section mb-5">
        <p class="section-label">About</p>
        <h2 class="section-title">{{ $hotel->name }}</h2>
        <p style="color:var(--muted);line-height:1.8;max-width:680px;">{{ $hotel->description }}</p>
    </div>
    @endif

    {{-- ROOM SHOWCASE --}}
    <div class="mb-5">
        <p class="section-label">What We Offer</p>
        <h2 class="section-title">Our Rooms</h2>
        <div class="row g-3">
            @foreach($roomTypes as $type => $rooms)
            @php $sample = $rooms->first(); $img = $sample->media->firstWhere('type','image'); @endphp
            <div class="col-md-4 col-sm-6">
                <div class="showcase-card">
                    @if($img)
                    <img src="{{ $img->url }}" alt="{{ ucfirst($type) }} room">
                    @else
                    <div style="height:200px;background:linear-gradient(135deg,#e8ece8,#d4d9d4);display:flex;align-items:center;justify-content:center;">
                        <i class="feather-image" style="font-size:32px;color:#b0b8b0;"></i>
                    </div>
                    @endif
                    <div class="card-body">
                        <span class="type-badge">{{ ucfirst($type) }}</span>
                        <div style="font-weight:700;font-size:17px;margin-bottom:4px;color:var(--brand-dark);">{{ ucfirst($type) }} Room</div>
                        <div style="color:var(--brand);font-weight:600;font-size:15px;">
                            From ₦{{ number_format($rooms->min('price_per_night') / 100, 0) }}
                            <span style="color:var(--muted);font-weight:400;font-size:13px;">/ night</span>
                        </div>
                        <div style="color:var(--muted);font-size:12px;margin-top:4px;">
                            {{ $rooms->count() }} room(s) available · Up to {{ $rooms->max('max_guests') }} guests
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- FOOTER --}}
<footer class="pub-footer">
    <div style="max-width:1100px;margin:0 auto;display:flex;flex-wrap:wrap;gap:40px;justify-content:space-between;align-items:flex-start;">
        <div>
            <h6>{{ $hotel->name }}</h6>
            @if($hotel->address)<p style="margin:0 0 4px;">{{ $hotel->address }}</p>@endif
            @if($hotel->city || $hotel->state)<p style="margin:0 0 4px;">{{ $hotel->city }}{{ $hotel->city && $hotel->state ? ', ' : '' }}{{ $hotel->state }}</p>@endif
        </div>
        <div>
            <h6>Contact</h6>
            @if($hotel->phone)<p style="margin:0 0 4px;"><a href="tel:{{ $hotel->phone }}">{{ $hotel->phone }}</a></p>@endif
            @if($hotel->email)<p style="margin:0;"><a href="mailto:{{ $hotel->email }}">{{ $hotel->email }}</a></p>@endif
        </div>
    </div>
    <div class="powered" style="max-width:1100px;margin:0 auto;padding-top:20px;border-top:1px solid rgba(255,255,255,.08);margin-top:20px;">
        Powered by <a href="https://africstayhms.com" style="color:rgba(255,255,255,.4);">AfricStay Hotel Management</a>
    </div>
</footer>

{{-- BOOKING MODAL --}}
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingModalLabel">
                    Complete Your Booking — Room <span id="modalRoomNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('public.booking.store', $hotel->slug) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="room_id"   id="form_room_id">
                    <input type="hidden" name="check_in"  id="form_check_in">
                    <input type="hidden" name="check_out" id="form_check_out">

                    <div class="summary-box" id="modalSummary">
                        <div class="s-row"><span class="s-label">Room</span>       <span class="s-val" id="s_room">—</span></div>
                        <div class="s-row"><span class="s-label">Check-in</span>   <span class="s-val" id="s_checkin">—</span></div>
                        <div class="s-row"><span class="s-label">Check-out</span>  <span class="s-val" id="s_checkout">—</span></div>
                        <div class="s-row"><span class="s-label">Duration</span>   <span class="s-val" id="s_duration">—</span></div>
                        <div class="s-row"><span class="s-label">Total</span>      <span class="s-val" id="s_total">—</span></div>
                        <div class="s-row"><span class="s-label">Payment</span>     <span class="s-val s-deposit" id="s_payment">Full amount due</span></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full name <span class="text-danger">*</span></label>
                            <input type="text" name="guest_name" class="form-control" placeholder="e.g. Amaka Johnson" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone number</label>
                            <input type="tel" name="guest_phone" class="form-control" placeholder="+234 800 000 0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email address</label>
                            <input type="email" name="guest_email" class="form-control" placeholder="you@example.com">
                        </div>
                        <div class="col-12">
                            <p style="font-size:12px;color:var(--muted);margin:0;">
                                Please provide at least a phone number or email so we can send your confirmation.
                            </p>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Special requests <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                            <textarea name="special_requests" rows="2" class="form-control" placeholder="e.g. early check-in, ground floor…"></textarea>
                        </div>
                    </div>

                    <div style="background:var(--surface);border-radius:8px;padding:14px 18px;margin-top:20px;font-size:13px;color:var(--muted);">
                        <i class="feather-lock" style="font-size:13px;margin-right:5px;color:var(--brand);"></i>
                        <strong style="color:var(--brand-dark);">Full payment</strong> is required to confirm your booking. You'll be redirected to a secure payment page.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-pay">
                        <i class="feather-credit-card" style="margin-right:6px;"></i> Pay Full Amount
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
const availUrl       = "{{ route('public.booking.availability', $hotel->slug) }}";

(function () {
    const pad    = n => String(n).padStart(2, '0');
    const now    = new Date();
    const local  = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    document.getElementById('check_in').min  = local;
    document.getElementById('check_out').min = local;

    const ci = new Date(now); ci.setHours(14, 0, 0, 0);
    const co = new Date(now); co.setDate(co.getDate()+1); co.setHours(12, 0, 0, 0);
    document.getElementById('check_in').value  = fmtDt(ci);
    document.getElementById('check_out').value = fmtDt(co);
})();

document.getElementById('check_in').addEventListener('change', function () {
    document.getElementById('check_out').min = this.value;
});

function fmtDt(d) {
    const pad = n => String(n).padStart(2,'0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fmtDisplayDt(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleString('en-NG', { weekday:'short', day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

document.getElementById('availabilityForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const checkIn  = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const guests   = document.getElementById('guest_count').value;
    const type     = document.getElementById('room_type').value;
    const btn      = document.getElementById('searchBtn');
    const errBox   = document.getElementById('searchError');
    const section  = document.getElementById('resultsSection');
    const list     = document.getElementById('resultsList');

    if (!checkIn || !checkOut) {
        errBox.textContent = 'Please select check-in and check-out date/time.';
        errBox.classList.remove('d-none');
        return;
    }
    if (checkOut <= checkIn) {
        errBox.textContent = 'Check-out must be after check-in.';
        errBox.classList.remove('d-none');
        return;
    }
    errBox.classList.add('d-none');

    btn.disabled    = true;
    btn.textContent = 'Searching…';
    list.innerHTML  = '';
    section.style.display = 'block';
    section.scrollIntoView({ behavior:'smooth', block:'start' });

    const params = new URLSearchParams({ check_in: checkIn, check_out: checkOut, guests });
    if (type) params.set('type', type);

    fetch(`${availUrl}?${params}`)
        .then(r => { if (!r.ok) throw new Error(`${r.status}`); return r.json(); })
        .then(rooms => {
            btn.disabled    = false;
            btn.textContent = 'Search';

            const inMs      = new Date(checkIn).getTime();
            const outMs     = new Date(checkOut).getTime();
            const diffHours = (outMs - inMs) / 3600000;

            if (!rooms.length) {
                list.innerHTML = `
                    <div class="col-12 text-center py-5" style="color:var(--muted);">
                        <i class="feather-calendar" style="font-size:36px;display:block;margin-bottom:12px;"></i>
                        No rooms available for those dates. Try adjusting your dates or room type.
                    </div>`;
                return;
            }

            list.innerHTML = rooms.map(r => {
                let units, unitLabel;
                switch (r.pricing_unit) {
                    case 'hour':
                        units = Math.max(1, Math.ceil(diffHours));
                        unitLabel = `${units} hour(s)`;
                        break;
                    case 'day24':
                        units = Math.max(1, Math.ceil(diffHours / 24));
                        unitLabel = `${units} × 24-hr block`;
                        break;
                    default:
                        units = Math.max(1, Math.ceil(diffHours / 24));
                        unitLabel = `${units} night(s)`;
                }
                const total = r.price_per_night_naira * units;

                return `
                <div class="col-md-4 col-sm-6">
                    <div class="room-card">
                        ${r.image
                            ? `<img class="room-img" src="${r.image}" alt="Room ${r.room_number}">`
                            : `<div class="room-img-placeholder"><i class="feather-image"></i></div>`}
                        <div class="card-body">
                            <div class="room-name">Room ${r.room_number}</div>
                            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:8px;">${r.name ?? ''}</div>
                            <div class="room-price">₦${num(r.price_per_night_naira)} <span style="color:var(--muted);font-size:13px;font-weight:400;">/ ${r.pricing_unit === 'hour' ? 'hour' : r.pricing_unit === 'day24' ? '24 hrs' : 'night'}</span></div>
                            <div class="room-meta">Up to ${r.max_guests} guest(s)</div>
                            <div class="room-total">Total: <strong>₦${num(total)}</strong> · ${unitLabel}</div>
                            <span class="full-payment-badge">Full Payment</span>
                            <button class="btn-book-room" onclick='openModal(${JSON.stringify(r)}, "${checkIn}", "${checkOut}", ${units}, ${total})'>
                                Book This Room →
                            </button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(err => {
            btn.disabled    = false;
            btn.textContent = 'Search';
            errBox.textContent = 'Could not check availability. Please try again.';
            errBox.classList.remove('d-none');
            console.error(err);
        });
});

function openModal(room, checkIn, checkOut, units, total) {
    document.getElementById('form_room_id').value  = room.id;
    document.getElementById('form_check_in').value = checkIn;
    document.getElementById('form_check_out').value= checkOut;
    document.getElementById('modalRoomNumber').textContent = room.room_number;

    document.getElementById('s_room').textContent     = `Room ${room.room_number}${room.name ? ' — '+room.name : ''}`;
    document.getElementById('s_checkin').textContent  = fmtDisplayDt(checkIn);
    document.getElementById('s_checkout').textContent = fmtDisplayDt(checkOut);
    document.getElementById('s_duration').textContent = `${units} ${room.pricing_unit === 'hour' ? 'hour(s)' : room.pricing_unit === 'day24' ? '24-hr block(s)' : 'night(s)'}`;
    document.getElementById('s_total').textContent    = '₦' + num(total);
    document.getElementById('s_payment').textContent  = 'Full amount due';

    new bootstrap.Modal(document.getElementById('bookingModal')).show();
}

function num(n) { return Number(n).toLocaleString('en-NG', {minimumFractionDigits:0, maximumFractionDigits:0}); }
</script>
</body>
</html>