@extends('layouts.hotel')
@section('title', 'Add Room')
@section('page_title', 'Add Room')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.rooms.index') }}">Rooms</a></li>
    <li class="breadcrumb-item active">Add Room</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('hotel.rooms.store') }}" method="POST" id="roomForm">
            @csrf

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Room number <span class="text-danger">*</span></label>
                    <input type="text" name="room_number" value="{{ old('room_number') }}"
                           class="form-control @error('room_number') is-invalid @enderror" required>
                    @error('room_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Name <span class="text-muted">(optional)</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control" placeholder="e.g. Garden Suite">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Type</label>
                    <select name="type" class="form-select">
                        @foreach($roomTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Floor <span class="text-muted">(optional)</span></label>
                    <input type="text" name="floor" value="{{ old('floor') }}" class="form-control">
                </div>
            </div>

            {{-- ── Pricing ──────────────────────────────────────────────────── --}}
            <div class="row align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Pricing unit <span class="text-danger">*</span></label>
                    <select name="pricing_unit" id="pricing_unit" class="form-select @error('pricing_unit') is-invalid @enderror"
                            onchange="updatePriceLabel(this.value)" required>
                        <option value="night" {{ old('pricing_unit','night') === 'night' ? 'selected' : '' }}>
                            Per night (calendar day)
                        </option>
                        <option value="hour"  {{ old('pricing_unit') === 'hour'  ? 'selected' : '' }}>
                            Per hour
                        </option>
                        <option value="day24" {{ old('pricing_unit') === 'day24' ? 'selected' : '' }}>
                            Per 24-hour block
                        </option>
                    </select>
                    @error('pricing_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold" id="priceLabelMain">
                        Price per night (₦) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">₦</span>
                        <input type="number" name="price_per_night" value="{{ old('price_per_night') }}"
                               class="form-control @error('price_per_night') is-invalid @enderror"
                               min="0" step="0.01" required placeholder="e.g. 15000">
                    </div>
                    @error('price_per_night')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted" id="priceHintMain">Charged once per calendar night.</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Max guests</label>
                    <input type="number" name="max_guests" value="{{ old('max_guests', 2) }}"
                           class="form-control" min="1" max="20">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Description <span class="text-muted">(optional)</span></label>
                <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold d-block">Photos &amp; Video</label>
                <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="openMediaWidget('image')">
                    <i class="feather-image me-1"></i> Add Photos
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openMediaWidget('video')">
                    <i class="feather-video me-1"></i> Add Video
                </button>
                <div id="mediaPreview" class="d-flex gap-2 mt-3 flex-wrap"></div>
            </div>

            <button type="submit" class="btn btn-primary px-5">
                <i class="feather-check-circle me-1"></i> Save Room
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
<script>
const CLOUD_NAME    = "{{ config('services.cloudinary.cloud_name') }}";
const UPLOAD_PRESET = "{{ config('services.cloudinary.upload_preset') }}";

const PRICE_LABELS = {
    night: 'Price per night (₦)',
    hour:  'Price per hour (₦)',
    day24: 'Price per 24-hour block (₦)',
};
const PRICE_HINTS = {
    night: 'Charged once per calendar night.',
    hour:  'Charged per hour — check-in/out times determine hours billed.',
    day24: 'Charged per rolling 24-hour block from check-in time.',
};

function updatePriceLabel(unit) {
    const lbl  = document.getElementById('priceLabelMain');
    const hint = document.getElementById('priceHintMain');
    if (lbl)  lbl.innerHTML  = (PRICE_LABELS[unit] || 'Price (₦)') + ' <span class="text-danger">*</span>';
    if (hint) hint.textContent = PRICE_HINTS[unit] || '';
}

// Set correct label on page load (respects old() value after validation failure)
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('pricing_unit');
    if (sel) updatePriceLabel(sel.value);
});

function openMediaWidget(mediaType) {
    cloudinary.openUploadWidget({
        cloudName: CLOUD_NAME, uploadPreset: UPLOAD_PRESET,
        sources: ['local','camera'], multiple: true,
        resourceType: mediaType, maxFiles: mediaType === 'video' ? 2 : 8,
    }, (error, result) => {
        if (!error && result.event === 'success') {
            const info      = result.info;
            const fieldName = mediaType === 'image' ? 'images' : 'videos';
            const form      = document.getElementById('roomForm');

            const urlInput   = document.createElement('input');
            urlInput.type    = 'hidden';
            urlInput.name    = `${fieldName}[][url]`;
            urlInput.value   = info.secure_url;
            form.appendChild(urlInput);

            const idInput   = document.createElement('input');
            idInput.type    = 'hidden';
            idInput.name    = `${fieldName}[][public_id]`;
            idInput.value   = info.public_id;
            form.appendChild(idInput);

            const preview = document.getElementById('mediaPreview');
            const thumb   = mediaType === 'image'
                ? `<img src="${info.secure_url}" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">`
                : `<div style="width:60px;height:60px;background:#212529;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;">
                       <i class="feather-video"></i>
                   </div>`;
            preview.insertAdjacentHTML('beforeend', thumb);
        }
    });
}
</script>
@endpush