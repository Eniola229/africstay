@extends('layouts.auth')
@section('title', 'Enter Verification Code')
@section('content')
<div class="auth-main">
    <div class="auth-left-panel">
        <div class="auth-left-inner">
            <div class="auth-panel-logo">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     style="height:32px;filter:brightness(0) invert(1);" alt="AfricStay">
                <span>AfricStay</span>
            </div>
            <div class="auth-panel-tag">Account Recovery</div>
            <h1>Check your<br>messages</h1>
            <p>We sent a 6-digit verification code by SMS. Enter it below along with your new password.</p>
            <div class="auth-panel-features">
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-clock"></i></div>
                    <div class="auth-feat-text">
                        <strong>Code expires in 10 minutes</strong>
                        <span>Didn't get it? You can request a new one</span>
                    </div>
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
            <h2>Enter verification code</h2>
            <p class="subtitle">Enter the code we texted you, then set a new password.</p>
            <form action="{{ route('password.otp.verify') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold">6-digit code</label>
                    <input type="text" name="otp" maxlength="6" inputmode="numeric"
                           placeholder="123456"
                           class="form-control form-control-lg @error('otp') is-invalid @enderror"
                           required>
                    @error('otp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">New password</label>
                    <input type="password" name="password"
                           placeholder="Min. 8 characters"
                           class="form-control form-control-lg @error('password') is-invalid @enderror"
                           required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Confirm new password</label>
                    <input type="password" name="password_confirmation"
                           placeholder="Repeat password"
                           class="form-control form-control-lg" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="feather-lock me-2"></i> Verify &amp; Reset Password
                </button>
                <p class="text-center text-muted fs-13 mt-3">
                    <a href="{{ route('password.request') }}" class="auth-link">Use a different email/phone</a>
                </p>
            </form>
        </div>
    </div>
</div>
@include('layouts.partials.auth-footer')
@endsection
