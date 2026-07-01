@extends('layouts.hotel')
@section('title', 'Settings')
@section('page_title', 'Hotel Settings')
@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Hotel Profile</h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.settings.profile') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hotel name</label>
                        <input type="text" name="name" value="{{ $hotel->name }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="text" name="phone" value="{{ $hotel->phone }}" class="form-control @error('phone') is-invalid @enderror" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Public email</label>
                            <input type="email" name="email" value="{{ $hotel->email }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <input type="text" name="address" value="{{ $hotel->address }}" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">City</label>
                            <input type="text" name="city" value="{{ $hotel->city }}" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">State</label>
                            <input type="text" name="state" value="{{ $hotel->state }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ $hotel->description }}</textarea>
                    </div>
                    <input type="hidden" name="logo_url" id="logo_url" value="{{ $hotel->logo }}">
                    <div class="mb-4">
                        <label class="form-label fw-bold d-block">Logo</label>
                        @if($hotel->logo)<img src="{{ $hotel->logo }}" style="height:50px;border-radius:6px;" class="mb-2 d-block">@endif
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openLogoWidget()">
                            <i class="feather-upload me-1"></i> Change Logo
                        </button>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if(in_array($hotel->tier, ['pro','enterprise']))
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Branding <span class="badge bg-warning text-dark ms-1">PRO</span></h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.settings.branding') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Booking page accent color</label>
                        <input type="color" name="brand_primary_color" value="{{ $hotel->brand_primary_color ?? '#2ECC71' }}" class="form-control form-control-color">
                        @error('brand_primary_color') <div class="text-danger fs-12">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Branding</button>
                </form>
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Online Booking</h5></div>
            <div class="card-body">
                <p class="text-muted fs-13">Your public booking page:</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="{{ route('public.hotel.show', $hotel->slug) }}" readonly id="publicLink">
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('publicLink').value)">
                        <i class="feather-copy"></i>
                    </button>
                </div>
                <a href="{{ route('public.hotel.show', $hotel->slug) }}" target="_blank" class="btn btn-sm btn-outline-primary mb-3">
                    <i class="feather-external-link me-1"></i> Preview
                </a>

                <form action="{{ route('hotel.settings.online-booking') }}" method="POST">
                    @csrf
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="online_booking_enabled" class="form-check-input" id="onlineToggle"
                               value="1" {{ $hotel->online_booking_enabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="onlineToggle">Accept online bookings</label>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Minimum deposit (%)</label>
                        <input type="number" name="online_booking_deposit_percent" value="{{ $hotel->online_booking_deposit_percent }}" 
                               min="10" max="100" class="form-control @error('online_booking_deposit_percent') is-invalid @enderror" readonly>
                        @error('online_booking_deposit_percent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Spec minimum is 100%, In our V2 you will be able to set min or mx for your online booking deposit.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">SEO title <span class="text-muted">(optional)</span></label>
                        <input type="text" name="meta_title" value="{{ $hotel->meta_title }}" class="form-control">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">SEO description <span class="text-muted">(optional)</span></label>
                        <textarea name="meta_description" rows="2" class="form-control">{{ $hotel->meta_description }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Online Booking Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
<script>
function openLogoWidget() {
    cloudinary.openUploadWidget({
        cloudName: "{{ config('services.cloudinary.cloud_name') }}",
        uploadPreset: "{{ config('services.cloudinary.upload_preset') }}",
        sources: ['local', 'camera'], cropping: true, multiple: false, resourceType: 'image',
    }, (error, result) => {
        if (!error && result.event === 'success') {
            document.getElementById('logo_url').value = result.info.secure_url;
        }
    });
}
</script>
@endpush
@endsection
