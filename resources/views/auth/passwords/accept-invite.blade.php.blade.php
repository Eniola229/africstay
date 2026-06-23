@extends('layouts.auth')
@section('title', 'Set Up Your Account')
@section('content')
<div class="auth-main">
    <div class="auth-left-panel">
        <div class="auth-left-inner">
            <div class="auth-panel-logo">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     style="height:32px;filter:brightness(0) invert(1);" alt="AfricStay">
                <span>AfricStay</span>
            </div>
            <div class="auth-panel-tag">Staff Invite</div>
            <h1>You've been<br>invited</h1>
            <p>{{ $staff->hotel->name ?? 'Your hotel' }} added you as
                <strong>{{ ucfirst(str_replace('_', ' ', $staff->role)) }}</strong> on AfricStay.
                Set a password to get started.</p>
            <div class="auth-panel-features">
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-user-check"></i></div>
                    <div class="auth-feat-text">
                        <strong>{{ $staff->name }}</strong>
                        <span>{{ $staff->email ?? $staff->phone }}</span>
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
            <h2>Set your password</h2>
            <p class="subtitle">Choose a password to activate your account.</p>
            <form action="{{ route('staff.invite.store', $token) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold">Password</label>
                    <input type="password" name="password"
                           class="form-control form-control-lg @error('password') is-invalid @enderror"
                           placeholder="Min. 8 characters" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Confirm password</label>
                    <input type="password" name="password_confirmation"
                           class="form-control form-control-lg" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="feather-check-circle me-2"></i> Activate My Account
                </button>
            </form>
        </div>
    </div>
</div>
@include('layouts.partials.auth-footer')
@endsection