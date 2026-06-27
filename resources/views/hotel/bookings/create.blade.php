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
                    <div id="guestResults" class="list-group position-absolute w-100" style="z-index:10;max-height:200px;overflow-y:auto;"></div>
                </div>
            </div>

            {{-- Guest Form Fields --}}
            <div class="row" id="guestFields">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Guest name <span class="text-danger">*</span></label>
                    <input type="text" name="guest_name" id="guest_name" 
                           class="form-control @error('guest_name') is-invalid @enderror" 
                           value="{{ old('guest_name') }}" required>
                    @error('guest_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Phone</label>
                    <input type="text" name="guest_phone" id="guest_phone" 
                           class="form-control" value="{{ old('guest_phone') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="guest_email" id="guest_email" 
                           class="form-control @error('guest_email') is-invalid @enderror" 
                           value="{{ old('guest_email') }}">
                    @error('guest_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Selected Guest Banner --}}
            <div id="selectedGuestBanner" class="alert alert-success d-none mb-3">
                <i class="feather-user-check me-1"></i>
                Using existing guest: <strong id="selectedGuestName"></strong>
                <button type="button" class="btn btn-sm btn-link" onclick="clearSelectedGuest()">Change</button>
            </div>

            <hr class="my-4">

            <h6 class="fw-bold mb-3">2. Dates, Times &amp; Room</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Check-in <span class="text-danger">*</span></label>
                    <input type="datetime-local"
                           name="check_in"
                           id="check_in"
                           class="form-control @error('check_in') is-invalid @enderror"
                           value="{{ old('check_in') }}"
                           required>
                    @error('check_in') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Check-out <span class="text-danger">*</span></label>
                    <input type="datetime-local"
                           name="check_out"
                           id="check_out"
                           class="form-control @error('check_out') is-invalid @enderror"
                           value="{{ old('check_out') }}"
                           required>
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
                    <button type="button" class="btn btn-outline-primary w-100" id="checkAvailBtn" onclick="checkAvailability()">
                        <i class="feather-search me-1"></i> Check Availability
                    </button>
                </div>
            </div>

            @error('room_id')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div id="availabilityError" class="alert alert-warning d-none mb-3"></div>
            <div id="availableRoomsList" class="row g-2 mb-4"></div>

            {{-- Summary card shown after a room is picked --}}
            <div id="bookingSummary" class="alert alert-info d-none mb-4">
                <strong>Selected:</strong>
                Room <span id="summaryRoom"></span> —
                <span id="summaryDuration"></span>
                @ ₦<span id="summaryRate"></span> / <span id="summaryUnit"></span>
                = <strong>₦<span id="summaryTotal"></span></strong>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Notes <span class="text-muted">(optional)</span></label>
                <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="feather-check-circle me-1"></i> Confirm Booking
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
const searchUrl    = "{{ route('hotel.guests.search') }}";
const availUrl     = "{{ route('hotel.bookings.available-rooms') }}";
let debounceTimer;
let pickedRoom = null;

// ── Guest autocomplete ────────────────────────────────────────────────────────
document.getElementById('guestSearch').addEventListener('input', function (e) {
    clearTimeout(debounceTimer);
    const q = e.target.value.trim();
    const resultsBox = document.getElementById('guestResults');
    
    if (q.length < 2) { 
        resultsBox.innerHTML = ''; 
        return; 
    }
    
    debounceTimer = setTimeout(() => {
        fetch(`${searchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(guests => {
                if (!guests.length) {
                    resultsBox.innerHTML = '<span class="list-group-item text-muted">No guests found</span>';
                    return;
                }
                resultsBox.innerHTML = guests.map(g =>
                    `<a href="#" class="list-group-item list-group-item-action"
                        onclick="selectGuest(event, '${g.id}', '${g.name.replace(/'/g,"\\'")}', '${g.phone || ''}', '${g.email || ''}')">
                        ${g.name}
                        <small class="text-muted ms-1">${g.phone ?? g.email ?? ''}</small>
                    </a>`
                ).join('');
            })
            .catch(() => {
                resultsBox.innerHTML = '<span class="list-group-item text-danger">Error loading guests</span>';
            });
    }, 300);
});

// Close results when clicking outside
document.addEventListener('click', function(e) {
    const search = document.getElementById('guestSearch');
    const results = document.getElementById('guestResults');
    if (!search.contains(e.target) && !results.contains(e.target)) {
        results.innerHTML = '';
    }
});

function selectGuest(e, id, name, phone, email) {
    e.preventDefault();
    
    // Set the hidden guest_id
    document.getElementById('guest_id').value = id;
    
    // Populate the form fields with guest data
    document.getElementById('guest_name').value = name;
    document.getElementById('guest_phone').value = phone;
    document.getElementById('guest_email').value = email;
    
    // Hide the guest fields section
    document.getElementById('guestFields').style.display = 'none';
    
    // Remove required attribute from guest_name since we have a guest_id
    document.getElementById('guest_name').removeAttribute('required');
    
    // Show the selected guest banner
    document.getElementById('selectedGuestBanner').classList.remove('d-none');
    document.getElementById('selectedGuestName').textContent = name;
    
    // Clear search and results
    document.getElementById('guestSearch').value = '';
    document.getElementById('guestResults').innerHTML = '';
    
    // Clear any validation errors
    document.getElementById('guest_name').classList.remove('is-invalid');
    document.getElementById('guest_email').classList.remove('is-invalid');
    const nameError = document.getElementById('guest_name').parentElement.querySelector('.invalid-feedback');
    const emailError = document.getElementById('guest_email').parentElement.querySelector('.invalid-feedback');
    if (nameError) nameError.style.display = 'none';
    if (emailError) emailError.style.display = 'none';
}

function clearSelectedGuest() {
    // Clear the hidden guest_id
    document.getElementById('guest_id').value = '';
    
    // Clear the form fields
    document.getElementById('guest_name').value = '';
    document.getElementById('guest_phone').value = '';
    document.getElementById('guest_email').value = '';
    
    // Show the guest fields section
    document.getElementById('guestFields').style.display = '';
    
    // Add required attribute back to guest_name
    document.getElementById('guest_name').setAttribute('required', 'required');
    
    // Hide the selected guest banner
    document.getElementById('selectedGuestBanner').classList.add('d-none');
}

// ── Room availability ─────────────────────────────────────────────────────────
function checkAvailability() {
    const type     = document.getElementById('room_type').value;
    const checkIn  = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const errBox   = document.getElementById('availabilityError');
    const list     = document.getElementById('availableRoomsList');
    const btn      = document.getElementById('checkAvailBtn');

    errBox.classList.add('d-none');
    errBox.textContent = '';

    if (!checkIn || !checkOut) {
        errBox.textContent = 'Please select both a check-in and check-out date/time first.';
        errBox.classList.remove('d-none');
        return;
    }
    if (checkOut <= checkIn) {
        errBox.textContent = 'Check-out must be after check-in.';
        errBox.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking…';
    list.innerHTML = '';
    resetSummary();

    const params = new URLSearchParams({ type, check_in: checkIn, check_out: checkOut });

    fetch(`${availUrl}?${params}`)
        .then(r => {
            if (!r.ok) throw new Error(`Server error ${r.status}`);
            return r.json();
        })
        .then(rooms => {
            btn.disabled = false;
            btn.innerHTML = '<i class="feather-search me-1"></i> Check Availability';

            if (!Array.isArray(rooms) || rooms.length === 0) {
                list.innerHTML = '<div class="col-12"><p class="text-muted mb-0">No rooms of this type are available for those dates.</p></div>';
                return;
            }

            list.innerHTML = rooms.map(r => {
                const unitLabel = unitToLabel(r.pricing_unit ?? 'night');
                return `
                <div class="col-md-3">
                    <div class="card room-pick h-100" style="cursor:pointer;"
                         onclick="pickRoom('${r.id}', ${r.price_per_night_naira}, '${r.pricing_unit ?? 'night'}', '${r.room_number}', this)">
                        <div class="card-body text-center">
                            <div class="fw-bold fs-5">Room ${r.room_number}</div>
                            <div class="text-muted small mt-1">
                                ₦${Number(r.price_per_night_naira).toLocaleString()} / ${unitLabel}
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="feather-search me-1"></i> Check Availability';
            errBox.textContent = 'Could not check availability. Please try again.';
            errBox.classList.remove('d-none');
            console.error(err);
        });
}

function pickRoom(id, priceNaira, pricingUnit, roomNumber, el) {
    document.getElementById('room_id').value = id;
    document.querySelectorAll('.room-pick').forEach(c => {
        c.classList.remove('border-primary', 'bg-light');
    });
    el.classList.add('border-primary', 'bg-light');

    pickedRoom = { id, priceNaira, pricingUnit, roomNumber };
    updateSummary();
}

// ── Booking summary ──────────────────────────────────────────────────────────
document.getElementById('check_in').addEventListener('change', updateSummary);
document.getElementById('check_out').addEventListener('change', updateSummary);

function updateSummary() {
    if (!pickedRoom) return;
    const checkIn  = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    if (!checkIn || !checkOut || checkOut <= checkIn) { resetSummary(); return; }

    const inMs  = new Date(checkIn).getTime();
    const outMs = new Date(checkOut).getTime();
    const diffMinutes = (outMs - inMs) / 60000;

    let units;
    switch (pickedRoom.pricingUnit) {
        case 'hour':
            units = Math.max(1, Math.ceil(diffMinutes / 60));
            break;
        case 'day24':
            units = Math.max(1, Math.ceil(diffMinutes / (60 * 24)));
            break;
        default:
            units = Math.max(1, Math.ceil(diffMinutes / (60 * 24)));
            break;
    }

    const total = units * pickedRoom.priceNaira;
    document.getElementById('summaryRoom').textContent     = pickedRoom.roomNumber;
    document.getElementById('summaryDuration').textContent = `${units} ${unitToLabel(pickedRoom.pricingUnit)}`;
    document.getElementById('summaryRate').textContent     = Number(pickedRoom.priceNaira).toLocaleString();
    document.getElementById('summaryUnit').textContent     = unitToLabel(pickedRoom.pricingUnit);
    document.getElementById('summaryTotal').textContent    = Number(total).toLocaleString();
    document.getElementById('bookingSummary').classList.remove('d-none');
}

function resetSummary() {
    pickedRoom = null;
    document.getElementById('bookingSummary').classList.add('d-none');
}

function unitToLabel(unit) {
    return unit === 'hour' ? 'hour' : unit === 'day24' ? '24-hr block' : 'night';
}
</script>
@endpush
@endsection