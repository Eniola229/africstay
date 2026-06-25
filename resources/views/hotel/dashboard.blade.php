@extends('layouts.hotel')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Overview</li>
@endsection
@section('page_actions')
    <a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary btn-sm">
        <i class="feather-plus me-1"></i> Add Room
    </a>
@endsection

@section('content')

@if($hotel->subscription_status === 'past_due')
<div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
    <span><i class="feather-alert-triangle me-2"></i> Your subscription has expired and you're in your grace period. Renew now to avoid losing access.</span>
    <a href="{{ route('hotel.subscription.plans') }}" class="btn btn-sm btn-dark">Renew</a>
</div>
@endif

<div class="row">
    <div class="col-xxl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Today's Revenue</p>
                        <h3 class="fw-bold mb-0">₦{{ number_format($stats['today_revenue']) }}</h3>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;">
                        <i class="feather-trending-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">This Week</p>
                        <h3 class="fw-bold mb-0">₦{{ number_format($stats['week_revenue']) }}</h3>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;">
                        <i class="feather-bar-chart-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">This Month</p>
                        <h3 class="fw-bold mb-0">₦{{ number_format($stats['month_revenue']) }}</h3>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FEF5E7;color:#F39C12;">
                        <i class="feather-calendar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Occupancy Rate</p>
                        <h3 class="fw-bold mb-0">{{ $stats['occupancy_rate'] }}%</h3>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FADBD8;color:#E74C3C;">
                        <i class="feather-home"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Room Status</h5>
                <a href="{{ route('hotel.rooms.index') }}" class="btn btn-sm btn-outline-primary">Manage Rooms</a>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded" style="background:#D5F5E3;">
                            <div class="fs-2 fw-bold" style="color:#1E8449;">{{ $stats['rooms_available'] }}</div>
                            <div class="fs-13 text-muted">Available</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded" style="background:#FADBD8;">
                            <div class="fs-2 fw-bold" style="color:#C0392B;">{{ $stats['rooms_occupied'] }}</div>
                            <div class="fs-13 text-muted">Occupied</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded" style="background:#FEF9E7;">
                            <div class="fs-2 fw-bold" style="color:#B7950B;">{{ $stats['rooms_dirty'] }}</div>
                            <div class="fs-13 text-muted">Dirty</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded" style="background:#EBF5FB;">
                            <div class="fs-2 fw-bold" style="color:#2980B9;">{{ $stats['rooms_maintenance'] }}</div>
                            <div class="fs-13 text-muted">Maintenance</div>
                        </div>
                    </div>
                </div>

                @if(($stats['rooms_available'] + $stats['rooms_occupied'] + $stats['rooms_dirty'] + $stats['rooms_maintenance']) === 0)
                <div class="text-center py-5 text-muted">
                    <i class="feather-home mb-2 d-block" style="font-size:36px;"></i>
                    <p class="mb-3">No rooms yet.</p>
                    <a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary btn-sm">Add your first room</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Bookings</h5>
                <a href="{{ route('hotel.bookings.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentBookings->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            @foreach($recentBookings as $booking)
                            @php
                                $badge = match($booking->status) {
                                    'pending' => 'bg-secondary', 'confirmed' => 'bg-info text-white', 'checked_in' => 'bg-success',
                                    'checked_out' => 'bg-dark', 'cancelled' => 'bg-danger', default => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td><a href="{{ route('hotel.bookings.show', $booking->id) }}" class="fw-semibold text-primary">{{ $booking->booking_reference }}</a></td>
                                <td>{{ $booking->guest->name }}</td>
                                <td>Room {{ $booking->room->room_number }}</td>
                                <td><span class="badge {{ $badge }} text-capitalize">{{ str_replace('_',' ',$booking->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">No bookings yet.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Outstanding Balances</h5></div>
            <div class="card-body p-0">
                @if($pendingPayments->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            @foreach($pendingPayments as $booking)
                            <tr>
                                <td><a href="{{ route('hotel.bookings.show', $booking->id) }}" class="text-primary fw-semibold">{{ $booking->guest->name }}</a></td>
                                <td class="fw-bold text-danger">₦{{ number_format($booking->balanceNaira(), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">No outstanding balances.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
