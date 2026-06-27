@extends('layouts.auth')
@section('title', 'Set Up Your Hotel')
@section('content')
<div class="auth-main" style="display:block; padding:40px 20px;">
<div style="max-width:900px; margin:0 auto;">

    <div class="text-center mb-4">
        <img src="{{ asset('dashboard/assets/images/favicon.png') }}" style="height:36px;" alt="AfricStay">
    </div>

    {{-- Progress steps --}}
    <div class="d-flex justify-content-center mb-5">
        @foreach(['Hotel Details','Choose Plan','Add Rooms','Invite Staff'] as $i => $label)
        @php $n = $i + 1; @endphp
        <div class="d-flex align-items-center">
            <div class="d-flex flex-column align-items-center" style="width:120px;">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                     style="width:36px;height:36px;
                            background:{{ $n <= $step ? 'var(--bs-primary,#2ECC71)' : '#e9ecef' }};
                            color:{{ $n <= $step ? '#fff' : '#6c757d' }};">
                    @if($n < $step)
                        <i class="feather-check"></i>
                    @else
                        {{ $n }}
                    @endif
                </div>
                <small class="mt-2 text-center {{ $n == $step ? 'fw-bold' : 'text-muted' }}" style="font-size:12px;">{{ $label }}</small>
            </div>
            @if($n < 4)
            <div style="width:40px;height:2px;background:{{ $n < $step ? 'var(--bs-primary,#2ECC71)' : '#e9ecef' }};margin-bottom:18px;"></div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">

            {{-- ================================================================
                 STEP 1 — HOTEL DETAILS
            ================================================================ --}}
            @if($step === 1)
            <h4 class="fw-bold mb-1">Tell us about {{ $hotel->name }}</h4>
            <p class="text-muted mb-4">This appears on receipts, your booking page, and guest notifications.</p>

            <form action="{{ route('onboarding.details') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Address <span class="text-danger">*</span></label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               class="form-control @error('address') is-invalid @enderror" required>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $hotel->phone) }}"
                               class="form-control @error('phone') is-invalid @enderror" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               class="form-control @error('city') is-invalid @enderror" required>
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">State <span class="text-danger">*</span></label>
                        <input type="text" name="state" value="{{ old('state') }}"
                               class="form-control @error('state') is-invalid @enderror" required>
                        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Public email <span class="text-muted">(optional)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Short description <span class="text-muted">(optional)</span></label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                </div>
                <input type="hidden" name="logo_url" id="logo_url">
                <div class="mb-4">
                    <label class="form-label fw-bold">Hotel logo <span class="text-muted">(optional)</span></label>
                    <div id="logoPreview" class="mb-2"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openLogoWidget()">
                        <i class="feather-upload me-1"></i> Upload Logo
                    </button>
                </div>

                <button type="submit" class="btn btn-primary btn-lg px-5">
                    Continue <i class="feather-arrow-right ms-1"></i>
                </button>
            </form>
            @endif

            {{-- ================================================================
                 STEP 2 — PLAN
            ================================================================ --}}
            @if($step === 2)
            <h4 class="fw-bold mb-1">Choose a plan</h4>
            <p class="text-muted mb-4">Pick monthly or yearly billing (yearly saves 20%). You'll pay securely via Flutterwave or Paystack, then continue setting up.</p>
            <a href="{{ route('hotel.subscription.plans') }}" class="btn btn-primary btn-lg px-5">
                View Plans &amp; Pay <i class="feather-arrow-right ms-1"></i>
            </a>
            @endif

            {{-- ================================================================
                 STEP 3 — ROOMS WITH PHOTOS/VIDEOS
            ================================================================ --}}
            @if($step === 3)
            <h4 class="fw-bold mb-1">Add your first rooms</h4>
            <p class="text-muted mb-4">Add a few rooms now — you can add more anytime from your dashboard.</p>

            @error('rooms')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <form action="{{ route('onboarding.rooms') }}" method="POST" id="roomsForm">
                @csrf
                <div id="roomsContainer"></div>

                <button type="button" class="btn btn-outline-primary mb-4" onclick="addRoomRow()">
                    <i class="feather-plus me-1"></i> Add Another Room
                </button>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        Save Rooms <i class="feather-arrow-right ms-1"></i>
                    </button>
                    <button type="submit" name="skip" value="1" class="btn btn-link text-muted">Skip for now</button>
                </div>
            </form>
            @endif

            {{-- ================================================================
                 STEP 4 — INVITE STAFF
            ================================================================ --}}
            @if($step === 4)
            <h4 class="fw-bold mb-1">Invite your first staff member</h4>
            <p class="text-muted mb-4">Optional — you can always invite staff later from Settings.</p>

            <form action="{{ route('onboarding.staff') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Role</label>
                    <select name="role" class="form-select">
                        <option value="manager">Manager</option>
                        <option value="receptionist" selected>Receptionist</option>
                        <option value="cashier">Cashier</option>
                        <option value="housekeeper">Housekeeper</option>
                        <option value="room_service">Room Service</option>
                        <option value="accountant">Accountant</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                <small class="text-muted d-block mb-4">Provide at least an email or phone so they get their invite link.</small>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="feather-send me-1"></i> Send Invite &amp; Finish
                    </button>
                    <button type="submit" name="skip" value="1" class="btn btn-link text-muted">Skip &amp; finish</button>
                </div>
            </form>
            @endif

        </div>
    </div>

</div>
</div>

@push('scripts')
<script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
<script>
const CLOUD_NAME    = "{{ config('services.cloudinary.cloud_name') }}";
const UPLOAD_PRESET = "{{ config('services.cloudinary.upload_preset') }}";
const ROOM_TYPES    = @json($roomTypes);

// ── Logo upload ───────────────────────────────────────────────────────────────
function openLogoWidget() {
    cloudinary.openUploadWidget({
        cloudName: CLOUD_NAME, uploadPreset: UPLOAD_PRESET,
        sources: ['local','camera'], cropping: true, multiple: false, resourceType: 'image',
    }, (error, result) => {
        if (!error && result.event === 'success') {
            document.getElementById('logo_url').value = result.info.secure_url;
            document.getElementById('logoPreview').innerHTML =
                `<img src="${result.info.secure_url}" style="height:60px;border-radius:8px;">`;
        }
    });
}

// ── Room rows ─────────────────────────────────────────────────────────────────
let roomIndex = 0;

const PRICING_UNIT_HINTS = {
    night: 'Charged once per calendar night.',
    hour:  'Charged per hour — check-in/out times determine hours billed.',
    day24: 'Charged per rolling 24-hour block from check-in time.',
};

function addRoomRow() {
    const i       = roomIndex++;
    const wrapper = document.createElement('div');
    wrapper.className = 'card mb-3 border';
    wrapper.id        = `room-card-${i}`;

    const typeOptions = ROOM_TYPES.map(t =>
        `<option value="${t}">${t.charAt(0).toUpperCase() + t.slice(1)}</option>`
    ).join('');

    wrapper.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong class="text-primary">Room ${i + 1}</strong>
                ${i > 0 ? `<button type="button" class="btn btn-sm btn-link text-danger p-0"
                    onclick="document.getElementById('room-card-${i}').remove()">
                    <i class="feather-trash-2 me-1"></i>Remove</button>` : ''}
            </div>

            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label fs-13 fw-bold">Room number <span class="text-danger">*</span></label>
                    <input type="text" name="rooms[${i}][room_number]" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-13 fw-bold">Type</label>
                    <select name="rooms[${i}][type]" class="form-select form-select-sm">
                        ${typeOptions}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-13 fw-bold">Pricing unit <span class="text-danger">*</span></label>
                    <select name="rooms[${i}][pricing_unit]" id="pricing_unit_${i}"
                            class="form-select form-select-sm"
                            onchange="updatePriceHint(${i}, this.value)">
                        <option value="night">Per night</option>
                        <option value="hour">Per hour</option>
                        <option value="day24">Per 24h block</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-13 fw-bold" id="price_label_${i}">
                        Price / night (₦) <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="rooms[${i}][price_per_night]"
                           class="form-control form-control-sm" min="0" step="0.01" required placeholder="e.g. 15000">
                    <small class="text-muted d-block mt-1" id="price_hint_${i}"
                           style="font-size:11px">${PRICING_UNIT_HINTS.night}</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-13 fw-bold">Max guests</label>
                    <input type="number" name="rooms[${i}][max_guests]"
                           class="form-control form-control-sm" value="2" min="1" max="20">
                </div>
            </div>

            <div class="mt-2 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="openRoomMediaWidget(${i}, 'image')">
                    <i class="feather-image me-1"></i> Add Photos
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="openRoomMediaWidget(${i}, 'video')">
                    <i class="feather-video me-1"></i> Add Video
                </button>
            </div>
            <div id="roomMediaPreview-${i}" class="d-flex gap-2 mt-2 flex-wrap"></div>
        </div>
    `;

    document.getElementById('roomsContainer').appendChild(wrapper);
}

function updatePriceHint(i, unit) {
    const label = document.getElementById(`price_label_${i}`);
    const hint  = document.getElementById(`price_hint_${i}`);

    const labelText = unit === 'hour'  ? 'Price / hour (₦)'      :
                      unit === 'day24' ? 'Price / 24h block (₦)'  :
                                         'Price / night (₦)';

    if (label) label.innerHTML = `${labelText} <span class="text-danger">*</span>`;
    if (hint)  hint.textContent = PRICING_UNIT_HINTS[unit] || '';
}

function openRoomMediaWidget(roomIdx, mediaType) {
    cloudinary.openUploadWidget({
        cloudName: CLOUD_NAME, uploadPreset: UPLOAD_PRESET,
        sources: ['local','camera'], multiple: true,
        resourceType: mediaType, maxFiles: mediaType === 'video' ? 2 : 8,
    }, (error, result) => {
        if (error || result.event !== 'success' || !result.info?.secure_url) return;

        const info      = result.info;
        const fieldName = mediaType === 'image' ? 'images' : 'videos';
        const form      = document.getElementById('roomsForm');

        const urlInput   = document.createElement('input');
        urlInput.type    = 'hidden';
        urlInput.name    = `rooms[${roomIdx}][${fieldName}][][url]`;
        urlInput.value   = info.secure_url;
        form.appendChild(urlInput);

        const idInput   = document.createElement('input');
        idInput.type    = 'hidden';
        idInput.name    = `rooms[${roomIdx}][${fieldName}][][public_id]`;
        idInput.value   = info.public_id ?? '';
        form.appendChild(idInput);

        const preview = document.getElementById(`roomMediaPreview-${roomIdx}`);
        const thumb   = mediaType === 'image'
            ? `<img src="${info.secure_url}" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">`
            : `<div style="width:50px;height:50px;background:#212529;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;">
                   <i class="feather-video"></i>
               </div>`;
        preview.insertAdjacentHTML('beforeend', thumb);
    });
}

// Start with one row on step 3
if (document.getElementById('roomsContainer')) {
    addRoomRow();
}
</script>
@endpush
@endsection