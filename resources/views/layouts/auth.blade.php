<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="AfricStay — Hotel & Guesthouse Management System for Africa." />
    <meta name="keywords" content="hotel management Nigeria, guesthouse software, hotel booking system, AfricStay" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AfricStay || @yield('title', 'Hotel Management System')</title>
    <meta property="og:title" content="AfricStay – Hotel & Guesthouse Management System" />
    <meta property="og:description" content="Manage bookings, payments, staff and reports for your hotel — all in one place." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:site_name" content="AfricStay" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('dashboard/assets/images/favicon.png') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/css/theme.min.css') }}" />
    {{-- AfricStay green brand override (cloned from the Orderer theme override pattern) --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/css/africstay-theme.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('head')
    @stack('styles')
</head>
<body>

@include('layouts.partials.alerts')

<div class="auth-main">
    @yield('content')
</div>

<script src="{{ asset('dashboard/assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/js/common-init.min.js') }}"></script>
@stack('scripts')
</body>
</html>