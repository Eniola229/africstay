<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AfricStay || @yield('title', 'Dashboard')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('dashboard/assets/images/favicon.png') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/vendors/css/daterangepicker.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/css/theme.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard/assets/css/africstay-theme.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('head')
    @stack('styles')
</head>
<body>

@include('layouts.partials.hotel-nav')
@include('layouts.partials.hotel-header')

<main class="nxl-container">
    <div class="nxl-content">

        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">@yield('page_title', 'Dashboard')</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('hotel.dashboard') }}">Account</a>
                    </li>
                    @yield('breadcrumb')
                </ul>
            </div>
            <div class="page-header-right ms-auto">
                @yield('page_actions')
            </div>
        </div>

        @include('layouts.partials.alerts')

        <div class="main-content">
            @yield('content')
        </div>

    </div>
</main>

@include('layouts.partials.hotel-footer')

<script src="{{ asset('dashboard/assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/vendors/js/daterangepicker.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/vendors/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/vendors/js/circle-progress.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/js/common-init.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/js/dashboard-init.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/js/theme-customizer-init.min.js') }}"></script>
@stack('scripts')
</body>
</html>