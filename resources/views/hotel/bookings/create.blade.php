@extends('layouts.hotel')
@section('title', 'New Walk-in Booking')
@section('page_title', 'New Walk-in Booking')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.bookings.index') }}">Bookings</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('hotel.bookings.store') }}" method="POST" id="bookingForm">
            @csrf
            <input type="hidden" name="guest_id" id="guest_id">
            <input type="hidden" name="room_id" id="room_id" required>

            <h6 class="fw-bold mb-3">1. Guest</h6>
            <div class="row">
                <div class="col-md-6 mb-3 position-relative">
                    <label class="form-label fw-bold">Search by name or phone</label>
                    <input type="text" id="guestSearch" class="form-control" placeholder="Start typing...">
                    <div id="guestResults" class="list-group position-absolute w-100" style="z-index:10;"></div>
                </div>
            </div>
            <div class="row" id="newGuestFields">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Guest name <span class="text-danger">*</span></label>
                    <input type="text" name="guest_name" id="guest_name" class="form-control @error('guest_name') is-invalid @enderror" required>
                    @error('guest_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Phone</label>
                    <input type="text" name="guest_phone" id="guest_phone" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="guest_email" id="guest_email" class="form-control @error('guest_email') is-invalid @enderror">
                    @error('guest_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div id="selectedGuestBanner" class="alert alert-success d-none mb-3">
                Using existing guest: <strong id="selectedGuestName"></strong>
                <button type="button" class="btn btn-sm btn-link" onclick="clearSelectedGuest()">Change</button>
            </div>

            <hr class="my-4">

            <h6 class="fw-bold mb-3">2. Dates &amp; Room</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Check-in <span class="text-danger">*</span></label>
                    <input type="date" name="check_in" id="check_in" class="form-control @error('check_in') is-invalid @enderror" required>
                    @error('check_in') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Check-out <span class="text-danger">*</span></label>
                    <input type="date" name="check_out" id="check_out" class="form-control @error('check_out') is-invalid @enderror" required>
                    @error('check_out') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Room type</label>
                    <select id="room_type" class="form-select">
                        @foreach($roomTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary w-100" onclick="checkAvailability()">
                        <i class="feather-search me-1"></i> Check Availability
                    </button>
                </div>
            </div>
            @error('room_id')<div class="alert alert-danger">{{ $message }}</div>@enderror

            <div id="availableRoomsList" class="row g-2 mb-4"></div>

            <div class="mb-4">
                <label class="form-label fw-bold">Notes <span class="text-muted">(optional)</span></label>
                <textarea name="notes" rows="2" class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="feather-check-circle me-1"></i> Confirm Booking
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
const searchUrl = "{{ route('hotel.guests.search') }}";
const availUrl = "{{ route('hotel.bookings.available-rooms') }}";
let debounceTimer;

document.getElementById('guestSearch').addEventListener('input', function (e) {
    clearTimeout(debounceTimer);
    const q = e.target.value;
    if (q.length < 2) { document.getElementById('guestResults').innerHTML = ''; return; }
    debounceTimer = setTimeout(() => {
        fetch(`${searchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(guests => {
                const box = document.getElementById('guestResults');
                box.innerHTML = guests.map(g =>
                    `<a href="#" class="list-group-item list-group-item-action" onclick="selectGuest(event,'${g.id}','${g.name.replace(/'/g,"\\'")}')">
                        ${g.name} <small class="text-muted">${g.phone ?? g.email ?? ''}</small>
                    </a>`
                ).join('');
            });
    }, 300);
});

function selectGuest(e, id, name) {
    e.preventDefault();
    document.getElementById('guest_id').value = id;
    document.getElementById('guestResults').innerHTML = '';
    document.getElementById('guestSearch').value = '';
    document.getElementById('newGuestFields').style.display = 'none';
    document.getElementById('guest_name').required = false;
    document.getElementById('selectedGuestBanner').classList.remove('d-none');
    document.getElementById('selectedGuestName').textContent = name;
}

function clearSelectedGuest() {
    document.getElementById('guest_id').value = '';
    document.getElementById('newGuestFields').style.display = 'flex';
    document.getElementById('guest_name').required = true;
    document.getElementById('selectedGuestBanner').classList.add('d-none');
}

function checkAvailability() {
    const type = document.getElementById('room_type').value;
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;

    if (!checkIn || !checkOut) { alert('Pick check-in and check-out dates first.'); return; }

    fetch(`${availUrl}?type=${type}&check_in=${checkIn}&check_out=${checkOut}`)
        .then(r => r.json())
        .then(rooms => {
            const list = document.getElementById('availableRoomsList');
            if (rooms.length === 0) {
                list.innerHTML = '<div class="col-12 text-muted">No rooms of this type are available for those dates.</div>';
                return;
            }
            list.innerHTML = rooms.map(r => `
                <div class="col-md-3">
                    <div class="card room-pick" style="cursor:pointer;" onclick="pickRoom('${r.id}', this)">
                        <div class="card-body text-center">
                            <div class="fw-bold">Room ${r.room_number}</div>
                            <div class="text-muted fs-13">₦${Number(r.price_per_night_naira).toLocaleString()}/night</div>
                        </div>
                    </div>
                </div>
            `).join('');
        });
}

function pickRoom(id, el) {
    document.getElementById('room_id').value = id;
    document.querySelectorAll('.room-pick').forEach(c => c.classList.remove('border-primary', 'bg-light'));
    el.classList.add('border-primary', 'bg-light');
}
</script>
@endpush
@endsection
