@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
<div class="auth-main">

    <div class="auth-left-panel">
        <div class="auth-left-inner">

            <div class="auth-panel-logo">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     style="height:32px;filter:brightness(0) invert(1);" alt="AfricStay">
                <span>AfricStay</span>
            </div>

            <div class="auth-panel-tag">Welcome Back</div>

            <h1>Run your hotel<br>from one screen</h1>
            <p>Sign in to manage bookings, check guests in and out, track payments, and keep your whole team in sync.</p>

            <div class="auth-panel-features">

                <div class="auth-feat-item">
                    <div class="auth-feat-icon">
                        <i class="feather-calendar"></i>
                    </div>
                    <div class="auth-feat-text">
                        <strong>Live room status</strong>
                        <span>See available, occupied and dirty rooms at a glance</span>
                    </div>
                </div>

                <div class="auth-feat-item">
                    <div class="auth-feat-icon">
                        <i class="feather-credit-card"></i>
                    </div>
                    <div class="auth-feat-text">
                        <strong>No more cash leakage</strong>
                        <span>Every payment tracked in your hotel wallet</span>
                    </div>
                </div>

                <div class="auth-feat-item">
                    <div class="auth-feat-icon">
                        <i class="feather-users"></i>
                    </div>
                    <div class="auth-feat-text">
                        <strong>Your whole team, one system</strong>
                        <span>Owners, managers, receptionists and housekeepers</span>
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

            <h2>Sign in</h2>
            <p class="subtitle">
                New to AfricStay?
                <a href="{{ route('register') }}" class="auth-link">Register your hotel</a>
            </p>

            <form action="{{ route('login') }}" method="POST" autocomplete="off">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold">Email or phone number</label>
                    <input type="text"
                           name="login"
                           value="{{ old('login') }}"
                           placeholder="you@hotel.com or 0801 234 5678"
                           class="form-control form-control-lg @error('login') is-invalid @enderror"
                           required>
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label fw-bold mb-0">Password</label>
                        <a href="{{ route('password.request') }}" class="auth-link fs-13">
                            Forgot password?
                        </a>
                    </div>
                    <input type="password"
                           name="password"
                           placeholder="Your password"
                           class="form-control form-control-lg @error('password') is-invalid @enderror mt-2"
                           required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label text-muted" for="remember">
                            Keep me signed in
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="feather-log-in me-2"></i> Sign In
                </button>

                <div class="auth-divider">or</div>

                <a href="{{ route('platform.login') }}"
                   class="btn btn-outline-secondary w-100 mt-3">
                    AfricStay team member? Sign in here
                </a>

            </form>
        </div>
    </div>

</div>
@endsection
@include('layouts.partials.auth-footer')