@extends('layouts.auth')
@section('title', 'Forgot Password')
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
            <h1>Forgot your<br>password?</h1>
            <p>Tell us the email or phone number on your account and we'll help you back in.</p>
            <div class="auth-panel-features">
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-mail"></i></div>
                    <div class="auth-feat-text">
                        <strong>Have an email on file?</strong>
                        <span>We'll send a secure reset link</span>
                    </div>
                </div>
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-smartphone"></i></div>
                    <div class="auth-feat-text">
                        <strong>Only a phone number?</strong>
                        <span>We'll text you a one-time verification code</span>
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
            <h2>Reset your password</h2>
            <p class="subtitle">Enter the email or phone number on your AfricStay account.</p>
            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold">Email or phone number</label>
                    <input type="text" name="login" value="{{ old('login') }}"
                           placeholder="you@hotel.com or 0801 234 5678"
                           class="form-control form-control-lg @error('login') is-invalid @enderror"
                           required>
                    @error('login')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="feather-send me-2"></i> Send Reset Instructions
                </button>
                <p class="text-center text-muted fs-13 mt-3">
                    Remembered it? <a href="{{ route('login') }}" class="auth-link">Back to sign in</a>
                </p>
            </form>
        </div>
    </div>
</div>
@include('layouts.partials.auth-footer')
@endsection