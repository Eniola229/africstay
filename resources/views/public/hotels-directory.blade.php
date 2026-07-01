<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Find Hotels & Guesthouses Near You | AfricStay</title>
    <meta name="description" content="Browse hotels and guesthouses across Nigeria on AfricStay. Check real-time room availability and book your stay online in minutes — no calls, no waiting.">
    <meta name="keywords" content="hotels Nigeria, guesthouses Nigeria, book hotel online, {{ $selectedState ?? 'Nigeria' }} hotels">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="Find Hotels & Guesthouses Near You | AfricStay">
    <meta property="og:description" content="Browse and book verified hotels and guesthouses across Nigeria on AfricStay.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Find Hotels & Guesthouses Near You | AfricStay">
    <meta name="twitter:description" content="Browse and book verified hotels and guesthouses across Nigeria on AfricStay.">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('ashboard/assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('ashboard/assets/css/bootstrap.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    {{--
        Structured data — built entirely in PHP first, then dumped as one
        json_encode() call. Keeping Blade directives (@foreach/@if) out of
        the <script> block avoids Blade misparsing raw JSON braces and the
        literal "@type" key (which otherwise looks like a Blade directive).
    --}}
    @php
        $itemListElements = [];
        foreach ($hotels as $i => $hotel) {
            $item = [
                '@type' => 'LodgingBusiness',
                'name'  => $hotel->name,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $hotel->city,
                    'addressRegion'   => $hotel->state,
                    'addressCountry'  => 'NG',
                ],
            ];

            if ($hotel->logo) {
                $item['image'] = $hotel->logo;
            }

            $itemListElements[] = [
                '@type'   => 'ListItem',
                'position' => $hotels->firstItem() + $i,
                'url'     => route('public.hotel.show', $hotel->slug),
                'item'    => $item,
            ];
        }

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type'    => 'ItemList',
            'name'     => 'Hotels on AfricStay',
            'itemListElement' => $itemListElements,
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <style>
        :root {
            --brand: #0a3622;
            --brand-light: #0a362218;
            --muted: #6a8a6a;
            --surface: #f0f5f0;
            --card-shadow: 0 4px 24px rgba(10,54,34,.08);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin:0; font-family:'Inter',sans-serif; background:var(--surface); color:#0f1c2e; font-size:15px; }

        .dir-nav {
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #e4e8ee;
        }
        .dir-nav .brand { display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--brand); font-weight:700; font-size:18px; }
        .dir-nav .brand img { height:30px; }

        .dir-hero {
            background: linear-gradient(160deg, #0a3622 0%, #1a5a3a 100%);
            padding: 64px 40px 120px;
            text-align: center;
            color: #fff;
        }
        .dir-hero h1 {
            font-family:'Playfair Display', serif;
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 700;
            margin: 0 0 12px;
        }
        .dir-hero p { color: rgba(255,255,255,.75); font-size:16px; max-width:560px; margin:0 auto; }

        .search-panel {
            max-width: 900px;
            margin: -70px auto 0;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 16px 48px rgba(10,54,34,.14);
            padding: 24px 28px;
            position: relative;
            z-index: 5;
        }
        .search-panel .form-label { font-size:11px; font-weight:600; letter-spacing:.7px; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
        .search-panel .form-control, .search-panel .form-select {
            border: 1.5px solid #e4e8ee; border-radius:8px; padding:10px 14px; font-size:14px;
        }
        .search-panel .form-control:focus, .search-panel .form-select:focus {
            border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-light); outline:none;
        }
        .btn-find {
            background: var(--brand); color:#fff; border:none; border-radius:8px;
            padding: 11px 24px; font-weight:600; font-size:14px; width:100%; cursor:pointer;
        }
        .btn-find:hover { opacity:.9; }

        .results-wrap { max-width:1100px; margin:56px auto 80px; padding:0 40px; }
        .results-count { color:var(--muted); font-size:14px; margin-bottom:20px; }

        .hotel-card {
            background:#fff; border-radius:14px; overflow:hidden;
            box-shadow: var(--card-shadow); text-decoration:none; color:inherit;
            display:flex; flex-direction:column; height:100%;
            transition: transform .2s, box-shadow .2s;
        }
        .hotel-card:hover { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(10,54,34,.14); color:inherit; }
        .hotel-card .hc-img { height:170px; width:100%; object-fit:cover; }
        .hotel-card .hc-img-placeholder {
            height:170px; background: linear-gradient(135deg,#e8ece8,#d4d9d4);
            display:flex; align-items:center; justify-content:center; font-size:32px; color:#b0b8b0;
        }
        .hotel-card .hc-body { padding:18px; flex:1; display:flex; flex-direction:column; }
        .hotel-card .hc-name { font-weight:700; font-size:16px; color:var(--brand); margin:0 0 4px; }
        .hotel-card .hc-loc { font-size:13px; color:var(--muted); margin-bottom:10px; }
        .hotel-card .hc-price { margin-top:auto; font-size:14px; font-weight:600; color:#0f1c2e; }
        .hotel-card .hc-price span { font-weight:400; color:var(--muted); font-size:12px; }
        .hotel-card .hc-cta { font-size:13px; color:var(--brand); font-weight:600; margin-top:8px; }

        .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
        .empty-state i { font-size:36px; display:block; margin-bottom:12px; }

        @media (max-width: 768px) {
            .dir-nav, .dir-hero, .results-wrap { padding-left:20px; padding-right:20px; }
            .search-panel { margin: -50px 16px 0; padding:20px; }
        }
    </style>
</head>
<body>

<nav class="dir-nav">
    <a class="brand" href="{{ route('home') }}">
        <img src="{{ asset('ashboard/assets/images/favicon.png') }}" alt="AfricStay">
        AfricStay
    </a>
    <a href="{{ route('home') }}" style="font-size:13px;color:var(--muted);text-decoration:none;">← Back to Home</a>
</nav>

<header class="dir-hero">
    <h1>Find Your Next Stay</h1>
    <p>Browse verified hotels and guesthouses across Nigeria — check availability and book instantly, no phone calls needed.</p>
</header>

<div class="search-panel">
    <form method="GET" action="{{ route('public.hotels.index') }}" class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Search by hotel, city, or state</label>
            <input type="text" name="q" class="form-control" placeholder="e.g. Lekki, Ibadan Grand Hotel..." value="{{ $search }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">State</label>
            <select name="state" class="form-select">
                <option value="">All states</option>
                @foreach($states as $s)
                <option value="{{ $s }}" {{ $selectedState === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-find">
                <i class="feather-search" style="font-size:13px;margin-right:5px;"></i> Search
            </button>
        </div>
    </form>
</div>

<div class="results-wrap">
    <p class="results-count">
        {{ $hotels->total() }} {{ Str::plural('hotel', $hotels->total()) }} found
        @if($search)
            for "<strong>{{ $search }}</strong>"
        @endif
        @if($selectedState)
            in <strong>{{ $selectedState }}</strong>
        @endif
    </p>

    @if($hotels->isEmpty())
        <div class="empty-state">
            <i class="feather-map-pin"></i>
            No hotels match your search. Try a different city or state.
        </div>
    @else
        <div class="row g-4">
            @foreach($hotels as $hotel)
                <div class="col-md-4 col-sm-6">
                    <a href="{{ route('public.hotel.show', $hotel->slug) }}" class="hotel-card">
                        @if($hotel->logo)
                            <img class="hc-img" src="{{ $hotel->logo }}" alt="{{ $hotel->name }}">
                        @else
                            <div class="hc-img-placeholder"><i class="feather-image"></i></div>
                        @endif
                        <div class="hc-body">
                            <p class="hc-name">{{ $hotel->name }}</p>
                            <p class="hc-loc">
                                <i class="feather-map-pin" style="font-size:12px;"></i>
                                {{ $hotel->city }}{{ $hotel->city && $hotel->state ? ', ' : '' }}{{ $hotel->state }}
                            </p>
                            @if($hotel->starting_price)
                                <p class="hc-price">
                                    From ₦{{ number_format($hotel->starting_price / 100, 0) }}
                                    <span>/ night</span>
                                </p>
                            @endif
                            <p class="hc-cta">View rooms & book →</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $hotels->links() }}
        </div>
    @endif
</div>

</body>
</html>