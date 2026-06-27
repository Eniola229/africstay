@extends('layouts.auth')
@section('title', 'Set New Password')
@section('content')
<div class="auth-main">
    <div class="auth-left-panel">
        <div class="auth-left-inner">
            <div class="auth-panel-logo">
                <img src="{{ asset('ashboard/assets/images/favicon.png') }}"
                     style="height:32px;filter:brightness(0) invert(1);" alt="AfricStay">
                <span>AfricStay</span>
            </div>
            <div class="auth-panel-tag">Account Recovery</div>
            <h1>Set a strong<br>new password</h1>
            <p>Choose a password that's hard to guess. We recommend mixing letters, numbers and symbols.</p>
            <div class="auth-panel-features">
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-check-circle"></i></div>
                    <div class="auth-feat-text">
                        <strong>At least 8 characters</strong>
                        <span>The longer the better for security</span>
                    </div>
                </div>
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-hash"></i></div>
                    <div class="auth-feat-text">
                        <strong>Include a number</strong>
                        <span>Required for every AfricStay account</span>
                    </div>
                </div>
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-shield"></i></div>
                    <div class="auth-feat-text">
                        <strong>Keep it unique</strong>
                        <span>Don't reuse passwords from other sites</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="auth-right-panel">
        <div class="auth-form-box">
            <a href="{{ route('home') }}">
                <img src="{{ asset('ashboard/assets/images/favicon.png') }}"
                     class="auth-logo-img" alt="AfricStay">
            </a>
            <h2>Set new password</h2>
            <p class="subtitle">Enter and confirm your new password below.</p>
            <form action="{{ route('password.reset') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email ?? old('email') }}">
                <div class="mb-4">
                    <label class="form-label fw-bold">New password</label>
                    <input type="password" name="password"
                           class="form-control form-control-lg @error('password') is-invalid @enderror"
                           placeholder="Min. 8 characters"
                           required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Confirm password</label>
                    <input type="password" name="password_confirmation"
                           class="form-control form-control-lg"
                           placeholder="Repeat password"
                           required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="feather-lock me-2"></i> Reset Password
                </button>
            </form>
        </div>
    </div>
</div>
@include('layouts.partials.auth-footer')
@endsection
