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

    <link rel="stylesheet" href="{{ asset('ashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ashboard/assets/css/africstay-theme.css') }}">
    @if(in_array($hotel->tier, ['pro','enterprise']) && $hotel->brand_primary_color)
    <style>:root { --bs-primary: {{ $hotel->brand_primary_color }} !important; }
        .btn-primary { background-color: {{ $hotel->brand_primary_color }} !important; border-color: {{ $hotel->brand_primary_color }} !important; }</style>
    @endif
    <style>
        .hero { background: linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.55)), #1B2631 center/cover no-repeat; color: #fff; padding: 90px 0 70px; }
        @if($hotel->logo)
        .hero { background-image: linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.55)), url('{{ $hotel->logo }}'); }
        @endif
        .room-card img { height: 180px; object-fit: cover; width: 100%; border-radius: 8px 8px 0 0; }
        .booking-widget { background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,.15); padding: 24px; margin-top: -60px; position: relative; z-index: 5; }
        .room-pick-card { cursor: pointer; transition: all .2s; }
        .room-pick-card.selected { border-color: var(--bs-primary,#2ECC71) !important; background: #f3fcf7; }
    </style>
</head>
<body>

<div class="hero text-center">
    <div class="container">
        @if($hotel->logo)<img src="{{ $hotel->logo }}" style="height:60px;border-radius:8px;" class="mb-3">@endif
        <h1 class="fw-bold">{{ $hotel->name }}</h1>
        <p class="fs-5">{{ $hotel->city }}{{ $hotel->city && $hotel->state ? ', ' : '' }}{{ $hotel->state }}</p>
    </div>
</div>

<div class="container">
    <div class="booking-widget">
        <h5 class="fw-bold mb-3">Check Availability</h5>
        <form id="availabilityForm" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-13 fw-bold">Check-in</label>
                <input type="date" id="check_in" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-13 fw-bold">Check-out</label>
                <input type="date" id="check_out" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13 fw-bold">Guests</label>
                <input type="number" id="guest_count" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-13 fw-bold">Room type</label>
                <select id="room_type" class="form-select">
                    @foreach($roomTypes->keys() as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="feather-search me-1"></i> Search</button>
            </div>
        </form>
    </div>

    @if(session('info'))
    <div class="alert alert-info mt-4">{{ session('info') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger mt-4">
        @foreach($errors->all() as $error)<p class="mb-0">{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="my-5" id="resultsSection" style="display:none;">
        <h5 class="fw-bold mb-3">Available Rooms</h5>
        <div class="row g-3" id="resultsList"></div>
    </div>

    @if($hotel->description)
    <div class="my-5">
        <h5 class="fw-bold mb-2">About {{ $hotel->name }}</h5>
        <p class="text-muted">{{ $hotel->description }}</p>
    </div>
    @endif

    <div class="my-5">
        <h5 class="fw-bold mb-3">Our Rooms</h5>
        <div class="row g-3">
            @foreach($roomTypes as $type => $rooms)
            @php $sample = $rooms->first(); $img = $sample->media->firstWhere('type','image'); @endphp
            <div class="col-md-4">
                <div class="card room-card h-100">
                    @if($img)
                    <img src="{{ $img->url }}" alt="{{ $type }}">
                    @else
                    <div style="height:180px;background:#f1f3f5;border-radius:8px 8px 0 0;display:flex;align-items:center;justify-content:center;">
                        <i class="feather-image text-muted" style="font-size:28px;"></i>
                    </div>
                    @endif
                    <div class="card-body">
                        <h6 class="fw-bold text-capitalize">{{ $type }}</h6>
                        <p class="text-muted fs-13 mb-1">From ₦{{ number_format($rooms->min('price_per_night') / 100, 2) }}/night</p>
                        <p class="text-muted fs-12">{{ $rooms->count() }} room(s) of this type</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="my-5 text-center text-muted">
        <p><i class="feather-phone me-1"></i> {{ $hotel->phone }} @if($hotel->email) &nbsp;·&nbsp; <i class="feather-mail me-1"></i> {{ $hotel->email }} @endif</p>
        @if($hotel->address)<p>{{ $hotel->address }}, {{ $hotel->city }}, {{ $hotel->state }}</p>@endif
    </div>
</div>

{{-- Booking modal --}}
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book Room <span id="modalRoomNumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('public.booking.store', $hotel->slug) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="form_room_id">
                    <input type="hidden" name="check_in" id="form_check_in">
                    <input type="hidden" name="check_out" id="form_check_out">

                    <div class="alert alert-light border" id="modalSummary"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Full name <span class="text-danger">*</span></label>
                        <input type="text" name="guest_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="text" name="guest_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="guest_email" class="form-control">
                        </div>
                    </div>
                    <small class="text-muted d-block mb-3">Please provide at least a phone number or email so we can send your booking confirmation.</small>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Special requests <span class="text-muted">(optional)</span></label>
                        <textarea name="special_requests" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="alert alert-info mb-0">
                        A deposit of <strong>{{ $hotel->online_booking_deposit_percent }}%</strong> is required to confirm this booking. You'll be redirected to a secure payment page next.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="feather-credit-card me-1"></i> Continue to Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('ashboard/assets/css/bootstrap.min.css') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
const availUrl = "{{ route('public.booking.availability', $hotel->slug) }}";
const depositPercent = {{ $hotel->online_booking_deposit_percent }};

document.getElementById('availabilityForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const type = document.getElementById('room_type').value;
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const guests = document.getElementById('guest_count').value;

    fetch(`${availUrl}?type=${type}&check_in=${checkIn}&check_out=${checkOut}&guests=${guests}`)
        .then(r => r.json())
        .then(rooms => {
            const section = document.getElementById('resultsSection');
            const list = document.getElementById('resultsList');
            section.style.display = 'block';

            if (rooms.length === 0) {
                list.innerHTML = '<div class="col-12 text-muted">No rooms available for those dates. Try different dates or a different room type.</div>';
                return;
            }

            const nights = Math.max(1, (new Date(checkOut) - new Date(checkIn)) / (1000*60*60*24));

            list.innerHTML = rooms.map(r => {
                const total = r.price_per_night_naira * nights;
                const deposit = total * (depositPercent / 100);
                return `
                <div class="col-md-4">
                    <div class="card room-pick-card h-100" onclick='openBookingModal(${JSON.stringify(r)}, "${checkIn}", "${checkOut}", ${nights})'>
                        ${r.image ? `<img src="${r.image}" style="height:160px;object-fit:cover;border-radius:8px 8px 0 0;">` : ''}
                        <div class="card-body">
                            <h6 class="fw-bold">Room ${r.room_number}</h6>
                            <p class="text-muted fs-13 mb-1">₦${Number(r.price_per_night_naira).toLocaleString()}/night × ${nights} night(s)</p>
                            <p class="fw-bold mb-1">Total: ₦${Number(total).toLocaleString()}</p>
                            <p class="text-success fs-13">Deposit due now: ₦${Number(deposit).toLocaleString()}</p>
                        </div>
                    </div>
                </div>`;
            }).join('');
        });
});

function openBookingModal(room, checkIn, checkOut, nights) {
    document.getElementById('form_room_id').value = room.id;
    document.getElementById('form_check_in').value = checkIn;
    document.getElementById('form_check_out').value = checkOut;
    document.getElementById('modalRoomNumber').textContent = room.room_number;

    const total = room.price_per_night_naira * nights;
    const deposit = total * (depositPercent / 100);
    document.getElementById('modalSummary').innerHTML =
        `${checkIn} → ${checkOut} (${nights} night(s))<br>Total: ₦${Number(total).toLocaleString()} · Deposit due now: <strong>₦${Number(deposit).toLocaleString()}</strong>`;

    new bootstrap.Modal(document.getElementById('bookingModal')).show();
}
</script>
</body>
</html>
