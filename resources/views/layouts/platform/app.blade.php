<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AfricStay Platform || @yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="{{ asset('ashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ashboard/assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ashboard/assets/css/theme.min.css') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('ashboard/assets/images/favicon.png') }}">
    @stack('styles')
</head>
<body>

@php $role = auth('platform')->user()->role; @endphp

<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('platform.dashboard') }}" class="b-brand">
                <img src="{{ asset('dashboard/assets/images/favicon.png') }}"
                     alt="AfricStay"
                     class="logo logo-lg"
                     style="width:140px;height:auto;display:block;margin:0 auto;" />
                <img src="{{ asset('ashboard/assets/images/favicon.png') }}"
                     alt="" class="logo logo-sm" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="nxl-navbar">

                <li class="nxl-item nxl-caption">
                    <label>Main</label>
                </li>

                <li class="nxl-item {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('platform.dashboard') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-airplay"></i></span>
                        <span class="nxl-mtext">Dashboard</span>
                    </a>
                </li>

                {{-- Hotels --}}
                @if(in_array($role, ['super_admin','operations','support','finance']))
                <li class="nxl-item nxl-caption">
                    <label>Management</label>
                </li>

                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('platform.hotels.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-home"></i></span>
                        <span class="nxl-mtext">Hotels</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.hotels.index') }}">All Hotels</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.hotels.index', ['subscription_status' => 'pending_payment']) }}">Pending Payment</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.hotels.index', ['subscription_status' => 'past_due']) }}">Past Due</a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- Enterprise Inquiries --}}
                @if(in_array($role, ['super_admin','support','operations']))
                <li class="nxl-item {{ request()->routeIs('platform.inquiries.*') ? 'active' : '' }}">
                    <a href="{{ route('platform.inquiries.index') }}" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-mail"></i></span>
                        <span class="nxl-mtext">Enterprise Inquiries</span>
                    </a>
                </li>
                @endif

                {{-- Revenue --}}
                @if(in_array($role, ['super_admin','finance']))
                <li class="nxl-item nxl-caption">
                    <label>Revenue</label>
                </li>

                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('platform.revenue.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                        <span class="nxl-mtext">Finance</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.revenue.index') }}">Revenue Reports</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.revenue.withdrawals') }}">Withdrawal Oversight</a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- Settings / Admin --}}
                @if($role === 'super_admin')
                <li class="nxl-item nxl-caption">
                    <label>Administration</label>
                </li>

                <li class="nxl-item nxl-hasmenu {{ request()->routeIs('platform.admins.*') ? 'active' : '' }}">
                    <a href="javascript:void(0);" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-shield"></i></span>
                        <span class="nxl-mtext">Platform Admins</span>
                        <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                    </a>
                    <ul class="nxl-submenu">
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.admins.index') }}">All Admins</a>
                        </li>
                        <li class="nxl-item">
                            <a class="nxl-link" href="{{ route('platform.admins.activity-log') }}">Activity Log</a>
                        </li>
                    </ul>
                </li>
                @endif

                <li class="nxl-item nxl-caption">
                    <label>Account</label>
                </li>

                <li class="nxl-item">
                    <a href="#" class="nxl-link">
                        <span class="nxl-micon"><i class="feather-user"></i></span>
                        <span class="nxl-mtext">
                            {{ auth('platform')->user()->name }}
                            <small class="d-block text-muted" style="font-size:11px;text-transform:capitalize;">{{ $role }}</small>
                        </span>
                    </a>
                </li>

                <li class="nxl-item">
                    <form method="POST" action="{{ route('platform.logout') }}">
                        @csrf
                        <button type="submit"
                                class="nxl-link border-0 bg-transparent w-100 text-start">
                            <span class="nxl-micon">
                                <i class="feather-power text-danger"></i>
                            </span>
                            <span class="nxl-mtext text-danger">Logout</span>
                        </button>
                    </form>
                </li>

            </ul>
        </div>
    </div>
</nav>

<div class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">@yield('page_title', 'Dashboard')</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('platform.dashboard') }}">AfricStay Platform</a>
                    </li>
                    @yield('breadcrumb')
                </ul>
            </div>
            <div class="page-header-right ms-auto">
                <span class="text-muted fs-13">
                    {{ auth('platform')->user()->name }}
                    · <span class="text-capitalize badge bg-secondary">{{ $role }}</span>
                </span>
            </div>
        </div>

        <div class="main-content">
            @include('layouts.partials.alerts')
            @yield('content')
        </div>
    </div>
</div>

<script src="{{ asset('dashboard/assets/vendors/js/vendors.min.js') }}"></script>
@stack('scripts')
</body>
</html>