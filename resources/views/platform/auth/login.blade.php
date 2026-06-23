@extends('layouts.auth')
@section('title', 'Platform Admin Sign In')
@section('content')
<div class="auth-main">

    <div class="auth-left-panel" style="background:#1B2631;">
        <div class="auth-left-inner">

            <div class="auth-panel-logo">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     style="height:32px;filter:brightness(0) invert(1);" alt="AfricStay">
                <span>AfricStay <small style="opacity:.6;font-weight:500;">Platform</small></span>
            </div>

            <div class="auth-panel-tag" style="background:rgba(255,255,255,.08);">Internal Team Only</div>

            <h1>AfricStay<br>Platform Admin</h1>
            <p>This is the internal AfricStay control panel — for our own team only. Hotel owners and staff should use the regular AfricStay login instead.</p>

            <div class="auth-panel-features">
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-shield"></i></div>
                    <div class="auth-feat-text">
                        <strong>Separate from hotel logins</strong>
                        <span>No hotel account can access this panel</span>
                    </div>
                </div>
                <div class="auth-feat-item">
                    <div class="auth-feat-icon"><i class="feather-eye"></i></div>
                    <div class="auth-feat-text">
                        <strong>Every action is logged</strong>
                        <span>All admin activity is recorded with IP and timestamp</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="auth-right-panel">
        <div class="auth-form-box">

            <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                 class="auth-logo-img" alt="AfricStay">

            <h2>Platform Admin Sign In</h2>
            <p class="subtitle">Internal access only. No self-registration.</p>

            <form action="{{ route('platform.login') }}" method="POST" autocomplete="off">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold">Email address</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="admin@africstayhms.com"
                           class="form-control form-control-lg @error('email') is-invalid @enderror"
                           required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Password</label>
                    <input type="password"
                           name="password"
                           placeholder="Your password"
                           class="form-control form-control-lg @error('password') is-invalid @enderror"
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

                <button type="submit" class="btn btn-dark btn-lg w-100 mb-3">
                    <i class="feather-lock me-2"></i> Sign In to Platform
                </button>

                <p class="text-center text-muted fs-13 mt-3">
                    Not an AfricStay team member?
                    <a href="{{ route('login') }}" class="auth-link">Go to the hotel login</a>
                </p>

            </form>
        </div>
    </div>

</div>
@endsection