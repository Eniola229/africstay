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
                    <input type="text" name="room_number" value="{{ old('room_number') }}" class="form-control @error('room_number') is-invalid @enderror" required>
                    @error('room_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Name <span class="text-muted">(optional)</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="e.g. Garden Suite">
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
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Price per night (₦) <span class="text-danger">*</span></label>
                    <input type="number" name="price_per_night" value="{{ old('price_per_night') }}" class="form-control @error('price_per_night') is-invalid @enderror" min="0" required>
                    @error('price_per_night') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Max guests</label>
                    <input type="number" name="max_guests" value="{{ old('max_guests', 2) }}" class="form-control" min="1" max="20">
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

@push('scripts')
<script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
<script>
const CLOUD_NAME = "{{ config('services.cloudinary.cloud_name') }}";
const UPLOAD_PRESET = "{{ config('services.cloudinary.upload_preset') }}";

function openMediaWidget(mediaType) {
    cloudinary.openUploadWidget({
        cloudName: CLOUD_NAME, uploadPreset: UPLOAD_PRESET, sources: ['local', 'camera'],
        multiple: true, resourceType: mediaType, maxFiles: mediaType === 'video' ? 2 : 8,
    }, (error, result) => {
        if (!error && result.event === 'success') {
            const info = result.info;
            const fieldName = mediaType === 'image' ? 'images' : 'videos';
            const form = document.getElementById('roomForm');

            const urlInput = document.createElement('input');
            urlInput.type = 'hidden';
            urlInput.name = `${fieldName}[][url]`;
            urlInput.value = info.secure_url;
            form.appendChild(urlInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = `${fieldName}[][public_id]`;
            idInput.value = info.public_id;
            form.appendChild(idInput);

            const preview = document.getElementById('mediaPreview');
            const thumb = mediaType === 'image'
                ? `<img src="${info.secure_url}" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">`
                : `<div style="width:60px;height:60px;background:#212529;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;"><i class="feather-video"></i></div>`;
            preview.insertAdjacentHTML('beforeend', thumb);
        }
    });
}
</script>
@endpush
@endsection
