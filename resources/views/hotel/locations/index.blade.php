@extends('layouts.hotel')
@section('title', 'Locations')
@section('page_title', 'Multi-Location Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Locations</li>
@endsection
@section('page_actions')
    @if($canAddLocation)
    <a href="{{ route('hotel.locations.create') }}" class="btn btn-primary btn-sm"><i class="feather-plus me-1"></i> Add Location</a>
    @endif
@endsection

@section('content')
<div class="row g-3 mb-3">
    @foreach($aggregated as $row)
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold">{{ $row['hotel']->name }}
                    @if($row['hotel']->isPrimaryLocation())<span class="badge bg-light text-dark">Primary</span>@endif
                </h6>
                <p class="text-muted fs-12 mb-3">{{ $row['hotel']->city }}, {{ $row['hotel']->state }}</p>
                <div class="row text-center">
                    <div class="col-4"><div class="fw-bold">{{ $row['rooms'] }}</div><div class="fs-11 text-muted">Rooms</div></div>
                    <div class="col-4"><div class="fw-bold">{{ $row['occupied'] }}</div><div class="fs-11 text-muted">Occupied</div></div>
                    <div class="col-4"><div class="fw-bold">₦{{ number_format($row['month_revenue']/100, 0) }}</div><div class="fs-11 text-muted">This Month</div></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if(! $canAddLocation && $hotel->tier === 'pro')
<p class="text-muted fs-13">You've reached the maximum of {{ \App\Models\Hotel::MAX_LOCATIONS_PRO }} locations on the Pro tier.</p>
@endif

<div class="alert alert-light border fs-13">
    <i class="feather-info me-2"></i>
    This is an aggregated read-only view across your locations. Day-to-day management of a child
    location's own rooms/bookings is on the roadmap — for now each location's data is tracked
    separately under the hood.
</div>
@endsection
