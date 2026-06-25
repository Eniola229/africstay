<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AfricStay Platform || Dashboard</title>
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/theme.min.css') }}">
</head>
<body style="background:#f4f6f8;">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="feather-shield me-2"></i> AfricStay Platform</h4>
        <form method="POST" action="{{ route('platform.logout') }}">
            @csrf
            <button class="btn btn-outline-dark btn-sm"><i class="feather-log-out me-1"></i> Logout</button>
        </form>
    </div>

    @include('layouts.partials.alerts')

    <div class="alert alert-info">
        <i class="feather-info me-2"></i>
        This is a placeholder for the Platform Admin dashboard (hotel management, enterprise inquiries,
        platform revenue reports, and admin settings — see spec section "Platform Admin Panel"). That
        full module is scoped for Phase 5; this view exists so the <code>platform</code> guard login
        flow has somewhere to land in Phase 1.
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Total Hotels</p>
                <h3 class="fw-bold mb-0">{{ \App\Models\Hotel::count() }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Active Subscriptions</p>
                <h3 class="fw-bold mb-0">{{ \App\Models\Hotel::where('subscription_status', 'active')->count() }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Pending Payment</p>
                <h3 class="fw-bold mb-0">{{ \App\Models\Hotel::where('subscription_status', 'pending_payment')->count() }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Past Due / Expired</p>
                <h3 class="fw-bold mb-0">{{ \App\Models\Hotel::whereIn('subscription_status', ['past_due','expired'])->count() }}</h3>
            </div></div>
        </div>
    </div>
</div>
</body>
</html>
