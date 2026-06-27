@extends('layouts.platform.app')
@section('title', 'Dashboard')
@section('page_title', 'Platform Dashboard')

@section('content')
<div class="row g-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <p class="text-muted fs-12 text-uppercase fw-semibold mb-1">Total Hotels</p>
            <h3 class="fw-bold mb-0">{{ \App\Models\Hotel::whereNull('parent_hotel_id')->count() }}</h3>
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

<div class="card mt-4">
    <div class="card-header"><h5 class="card-title mb-0">Quick Links</h5></div>
    <div class="card-body">
        <a href="{{ route('platform.hotels.index') }}" class="btn btn-outline-dark btn-sm me-2">Browse Hotels</a>
        <a href="{{ route('platform.inquiries.index') }}" class="btn btn-outline-dark btn-sm me-2">Enterprise Inquiries</a>
        @if(auth('platform')->user()->role === 'super_admin')
        <a href="{{ route('platform.admins.index') }}" class="btn btn-outline-dark btn-sm">Manage Platform Admins</a>
        @endif
    </div>
</div>
@endsection
