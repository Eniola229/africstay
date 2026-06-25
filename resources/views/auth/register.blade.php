@extends('layouts.auth')
@section('title', 'Register Your Hotel')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css"/>
@endpush
<div class="auth-main">

    <div class="auth-left-panel">
        <div class="auth-left-inner">

            <div class="auth-panel-logo">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     style="height:32px;filter:brightness(0) invert(1);" alt="AfricStay">
                <span>AfricStay</span>
            </div>

            <div class="auth-panel-tag">For Hotel Owners</div>

            <h1>Get your hotel<br>online in minutes</h1>
            <p>Set up rooms, take online bookings, and get paid straight into your hotel wallet — built for hotels and guesthouses across Africa.</p>

            <div class="auth-panel-features">

                <div class="auth-feat-item">
                    <div class="auth-feat-icon">
                        <i class="feather-shield"></i>
                    </div>
                    <div class="auth-feat-text">
                        <strong>Secure virtual accounts</strong>
                        <span>Every guest gets a unique payment account at check-in</span>
                    </div>
                </div>

                <div class="auth-feat-item">
                    <div class="auth-feat-icon">
                        <i class="feather-globe"></i>
                    </div>
                    <div class="auth-feat-text">
                        <strong>Your own booking page</strong>
                        <span>A public page guests can use to book directly</span>
                    </div>
                </div>

                <div class="auth-feat-item">
                    <div class="auth-feat-icon">
                        <i class="feather-bar-chart-2"></i>
                    </div>
                    <div class="auth-feat-text">
                        <strong>Real revenue visibility</strong>
                        <span>Daily, weekly and monthly reports, automatically</span>
                    </div>
                </div>

                <div class="auth-feat-item">
                    <div class="auth-feat-icon">
                        <i class="feather-layers"></i>
                    </div>
                    <div class="auth-feat-text">
                        <strong>Pick a plan that fits</strong>
                        <span>From a single guesthouse to multi-location lodges</span>
                    </div>
                </div>

            </div>

            <div class="auth-trust-bar">
                <div class="auth-trust-avatars">
                    <span>GL</span>
                    <span>RE</span>
                    <span>OK</span>
                    <span>+</span>
                </div>
                <div class="auth-trust-text">
                    <strong>Trusted by hotels across Nigeria</strong>
                    <span>From guesthouses to multi-location lodges</span>
                </div>
            </div>

        </div>
    </div>

    <div class="auth-right-panel">
        <div class="auth-form-box">

            <a href="{{ route('home') }}">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     class="auth-logo-img" alt="AfricStay">
            </a>

            <h2>Register your hotel</h2>
            <p class="subtitle">
                Already on AfricStay?
                <a href="{{ route('login') }}" class="auth-link">Sign in</a>
            </p>

            <form action="{{ route('register') }}" method="POST" autocomplete="off">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">Your name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Chidi Obi"
                           class="form-control @error('name') is-invalid @enderror"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Hotel / guesthouse name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="hotel_name"
                           value="{{ old('hotel_name') }}"
                           placeholder="Grand Lodge Enugu"
                           class="form-control @error('hotel_name') is-invalid @enderror"
                           required>
                    @error('hotel_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted fs-12">You can fine-tune address and details in the next step.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Phone number <span class="text-danger">*</span></label><br>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           value="{{ old('phone') }}"
                           class="form-control @error('phone') is-invalid @enderror">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted fs-12">Used to log in and as your hotel's fallback contact number.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Email address <span class="text-muted">(optional)</span></label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="you@hotel.com"
                           class="form-control @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password"
                               name="password"
                               placeholder="Min. 8 characters"
                               class="form-control @error('password') is-invalid @enderror"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Confirm password <span class="text-danger">*</span></label>
                        <input type="password"
                               name="password_confirmation"
                               placeholder="Repeat"
                               class="form-control"
                               required>
                    </div>
                </div>
                <small class="text-muted fs-12 d-block mb-4">Must be at least 8 characters and include a number.</small>

                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="terms" id="terms" class="form-check-input" required>
                        <label class="form-check-label text-muted fs-13" for="terms">
                            I agree to AfricStay's
                            <a href="#" class="auth-link">Terms of Service</a> and
                            <a href="#" class="auth-link">Privacy Policy</a>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="feather-home me-2"></i> Create My Hotel Account
                </button>

                <p class="text-center text-muted" style="font-size:13px; margin-top:14px;">
                    Next, we'll walk you through hotel details, plan selection, rooms and staff —
                    each step can be skipped and finished later.
                </p>

            </form>
        </div>
    </div>

</div>
@include('layouts.partials.auth-footer')

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script>
const input = document.querySelector("#phone");
const iti = window.intlTelInput(input, {
    initialCountry: "ng",
    separateDialCode: true,
    preferredCountries: ["ng", "gh", "ke", "za"],
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
});
const form = input.closest("form");
form.addEventListener("submit", function () {
    input.value = iti.getNumber();
});
</script>
@endpush
@endsection
