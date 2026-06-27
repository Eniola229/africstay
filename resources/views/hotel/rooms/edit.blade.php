@extends('layouts.hotel')
@section('title', 'Edit Room ' . $room->room_number)
@section('page_title', 'Edit Room')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.rooms.index') }}">Rooms</a></li>
    <li class="breadcrumb-item active">Room {{ $room->room_number }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-7">

        {{-- ── Room details ──────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Room Details</h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.rooms.update', $room->id) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type</label>
                            <select name="type" class="form-select">
                                @foreach($roomTypes as $type)
                                    <option value="{{ $type }}" {{ $room->type === $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Floor</label>
                            <input type="text" name="floor" value="{{ $room->floor }}" class="form-control">
                        </div>
                    </div>

                    {{-- ── Pricing ──────────────────────────────────────────── --}}
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Pricing unit <span class="text-danger">*</span></label>
                            <select name="pricing_unit" id="pricing_unit_edit" class="form-select"
                                    onchange="updateEditPriceLabel(this.value)" required>
                                <option value="night" {{ ($room->pricing_unit ?? 'night') === 'night' ? 'selected' : '' }}>
                                    Per night (calendar day)
                                </option>
                                <option value="hour"  {{ ($room->pricing_unit ?? '') === 'hour'  ? 'selected' : '' }}>
                                    Per hour
                                </option>
                                <option value="day24" {{ ($room->pricing_unit ?? '') === 'day24' ? 'selected' : '' }}>
                                    Per 24-hour block
                                </option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold" id="editPriceLabelMain">
                                Price per night (₦) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="price_per_night"
                                       value="{{ $room->pricePerNightNaira() }}"
                                       class="form-control" min="0" step="0.01" required>
                            </div>
                            <small class="text-muted" id="editPriceHintMain"></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Max guests</label>
                            <input type="number" name="max_guests" value="{{ $room->max_guests }}"
                                   class="form-control" min="1" max="20">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ $room->description }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        {{-- ── Block for Maintenance ─────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Block for Maintenance</h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.rooms.maintenance', $room->id) }}" method="POST"
                      onsubmit="return confirm('Block Room {{ $room->room_number }} for maintenance?');">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason</label>
                        <input type="text" name="maintenance_reason"
                               class="form-control @error('maintenance_reason') is-invalid @enderror" required>
                        @error('maintenance_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Expected return date</label>
                        <input type="date" name="maintenance_expected_return" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="feather-tool me-1"></i> Block Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Photos & Video ───────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Photos &amp; Video</h5>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openMediaWidget('image')">
                        <i class="feather-image me-1"></i> Add Photos
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openMediaWidget('video')">
                        <i class="feather-video me-1"></i> Add Video
                    </button>
                </div>

                <div class="row g-2">
                    @forelse($room->media as $media)
                    <div class="col-4">
                        <div class="position-relative">
                            @if($media->type === 'image')
                                <img src="{{ $media->url }}" style="width:100%;height:80px;object-fit:cover;border-radius:8px;">
                            @else
                                <video src="{{ $media->url }}" style="width:100%;height:80px;object-fit:cover;border-radius:8px;" muted></video>
                            @endif
                            <form action="{{ route('hotel.rooms.media.destroy', [$room->id, $media->id]) }}" method="POST"
                                  class="position-absolute top-0 end-0 m-1"
                                  onsubmit="return confirm('Remove this media?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger p-1" style="line-height:1;">
                                    <i class="feather-x" style="font-size:11px;"></i>
                                </button>
                            </form>
                            @if($media->is_primary)
                                <span class="badge bg-success position-absolute bottom-0 start-0 m-1"
                                      style="font-size:9px;">Primary</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center text-muted py-4">
                        <i class="feather-image mb-2 d-block" style="font-size:28px;"></i>
                        No photos or videos yet.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
<script>
const CLOUD_NAME    = "{{ config('services.cloudinary.cloud_name') }}";
const UPLOAD_PRESET = "{{ config('services.cloudinary.upload_preset') }}";
const ADD_MEDIA_URL = "{{ route('hotel.rooms.media.store', $room->id) }}";
const CSRF_TOKEN    = "{{ csrf_token() }}";

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

function updateEditPriceLabel(unit) {
    const lbl  = document.getElementById('editPriceLabelMain');
    const hint = document.getElementById('editPriceHintMain');
    if (lbl)  lbl.innerHTML  = (PRICE_LABELS[unit] || 'Price (₦)') + ' <span class="text-danger">*</span>';
    if (hint) hint.textContent = PRICE_HINTS[unit] || '';
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('pricing_unit_edit');
    if (sel) updateEditPriceLabel(sel.value);
});

function openMediaWidget(mediaType) {
    cloudinary.openUploadWidget({
        cloudName: CLOUD_NAME, uploadPreset: UPLOAD_PRESET,
        sources: ['local','camera'], multiple: true,
        resourceType: mediaType, maxFiles: mediaType === 'video' ? 2 : 8,
    }, (error, result) => {
        if (!error && result.event === 'success') {
            const info = result.info;
            fetch(ADD_MEDIA_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ type: mediaType, url: info.secure_url, public_id: info.public_id }),
            }).then(() => window.location.reload());
        }
    });
}
</script>
@endpush