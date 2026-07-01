<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AfricStay Platform || @yield('title', 'Dashboard')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('ashboard/assets/images/favicon.png') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('ashboard/assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('ashboard/assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('ashboard/assets/vendors/css/daterangepicker.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('ashboard/assets/css/theme.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('ashboard/assets/css/orderer-theme.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('head')
    @stack('styles')
</head>
<body>

@php $role = auth('platform')->user()->role; @endphp

@include('layouts.partials.platform-nav')
@include('layouts.partials.platform-header')

<main class="nxl-container">
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
                @yield('page_actions')
            </div>
        </div>

        @include('layouts.partials.alerts')

        <div class="main-content">
            @yield('content')
        </div>
    </div>
</main>

@include('layouts.partials.platform-footer')

<script src="{{ asset('ashboard/assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('ashboard/assets/vendors/js/daterangepicker.min.js') }}"></script>
<script src="{{ asset('ashboard/assets/vendors/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('ashboard/assets/vendors/js/circle-progress.min.js') }}"></script>
<script src="{{ asset('ashboard/assets/js/common-init.min.js') }}"></script>
<script src="{{ asset('ashboard/assets/js/dashboard-init.min.js') }}"></script>
<script src="{{ asset('ashboard/assets/js/theme-customizer-init.min.js') }}"></script>
@stack('scripts')
</body>
</html>