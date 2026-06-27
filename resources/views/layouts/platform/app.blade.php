<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AfricStay Platform || @yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/theme.min.css') }}">
    <style>
        body { background: #f4f6f8; }
        .platform-nav { background: #1B2631; min-height: 100vh; padding: 24px 0; }
        .platform-nav a { color: rgba(255,255,255,.7); display: block; padding: 10px 24px; text-decoration: none; font-size: 14px; }
        .platform-nav a:hover, .platform-nav a.active { color: #fff; background: rgba(255,255,255,.08); }
        .platform-nav .brand { color: #fff; font-weight: 700; padding: 0 24px 24px; font-size: 18px; }
        .platform-nav .caption { color: rgba(255,255,255,.4); font-size: 11px; text-transform: uppercase; padding: 16px 24px 6px; }
    </style>
    @stack('styles')
</head>
<body>
<div class="d-flex">
    <div class="platform-nav" style="width:240px;flex-shrink:0;">
        <div class="brand"><i class="feather-shield me-2"></i> AfricStay Platform</div>

        <a href="{{ route('platform.dashboard') }}" class="{{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">Dashboard</a>

        @php $role = auth('platform')->user()->role; @endphp

        @if(in_array($role, ['super_admin','operations','support','finance']))
        <div class="caption">Hotels</div>
        <a href="{{ route('platform.hotels.index') }}" class="{{ request()->routeIs('platform.hotels.*') ? 'active' : '' }}">All Hotels</a>
        @endif

        @if(in_array($role, ['super_admin','support','operations']))
        <a href="{{ route('platform.inquiries.index') }}" class="{{ request()->routeIs('platform.inquiries.*') ? 'active' : '' }}">Enterprise Inquiries</a>
        @endif

        @if(in_array($role, ['super_admin','finance']))
        <div class="caption">Revenue</div>
        <a href="{{ route('platform.revenue.index') }}" class="{{ request()->routeIs('platform.revenue.index') ? 'active' : '' }}">Revenue Reports</a>
        <a href="{{ route('platform.revenue.withdrawals') }}" class="{{ request()->routeIs('platform.revenue.withdrawals') ? 'active' : '' }}">Withdrawal Oversight</a>
        @endif

        @if($role === 'super_admin')
        <div class="caption">Settings</div>
        <a href="{{ route('platform.admins.index') }}" class="{{ request()->routeIs('platform.admins.index') ? 'active' : '' }}">Platform Admins</a>
        <a href="{{ route('platform.admins.activity-log') }}" class="{{ request()->routeIs('platform.admins.activity-log') ? 'active' : '' }}">Activity Log</a>
        @endif

        <div class="caption">Account</div>
        <form method="POST" action="{{ route('platform.logout') }}">
            @csrf
            <button type="submit" class="border-0 bg-transparent w-100 text-start" style="color:rgba(255,255,255,.7);padding:10px 24px;font-size:14px;">
                <i class="feather-log-out me-1"></i> Logout
            </button>
        </form>
    </div>

    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">@yield('page_title', 'Dashboard')</h4>
            <span class="text-muted fs-13">{{ auth('platform')->user()->name }} · <span class="text-capitalize">{{ $role }}</span></span>
        </div>

        @include('layouts.partials.alerts')

        @yield('content')
    </div>
</div>

<script src="{{ asset('dashboard/assets/vendors/js/vendors.min.js') }}"></script>
@stack('scripts')
</body>
</html>
