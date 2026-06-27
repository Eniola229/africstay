@extends('layouts.platform.app')
@section('title', 'Dashboard')
@section('page_title', 'Platform Dashboard')

@section('content')

{{-- ── Revenue & Hotel top stats ──────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">MRR</p>
                        <h2 class="fw-bold mb-0 text-success">₦{{ number_format($mrr / 100, 2) }}</h2>
                        <small class="text-muted">monthly recurring revenue</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;flex-shrink:0;">
                        <i class="feather-trending-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Revenue This Month</p>
                        <h2 class="fw-bold mb-0">₦{{ number_format($revenueThisMonth / 100, 2) }}</h2>
                        @php $revChange = $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1) : null; @endphp
                        @if($revChange !== null)
                        <small class="{{ $revChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="feather-{{ $revChange >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($revChange) }}% vs last month
                        </small>
                        @else
                        <small class="text-muted">subscription payments</small>
                        @endif
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Total Hotels</p>
                        <h2 class="fw-bold mb-0">{{ number_format($hotelStats['total']) }}</h2>
                        <small class="text-success">+{{ $hotelStats['new_this_month'] }} this month</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#F4ECF7;color:#8E44AD;flex-shrink:0;">
                        <i class="feather-home"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Active Subscriptions</p>
                        <h2 class="fw-bold mb-0 text-success">{{ number_format($hotelStats['active']) }}</h2>
                        <small class="text-muted">of {{ $hotelStats['total'] }} total hotels</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;flex-shrink:0;">
                        <i class="feather-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Secondary stats row ─────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Pending Payment</p>
                        <h2 class="fw-bold mb-0 text-warning">{{ number_format($hotelStats['pending_payment']) }}</h2>
                        <small class="text-muted">awaiting first payment</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FEF9E7;color:#F39C12;flex-shrink:0;">
                        <i class="feather-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Past Due / Expired</p>
                        <h2 class="fw-bold mb-0 text-danger">{{ number_format($hotelStats['past_due'] + $hotelStats['expired']) }}</h2>
                        <small class="text-muted">need attention</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FADBD8;color:#E74C3C;flex-shrink:0;">
                        <i class="feather-alert-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Pending Withdrawals</p>
                        <h2 class="fw-bold mb-0 text-warning">{{ number_format($withdrawalStats['pending_count']) }}</h2>
                        <small class="text-muted">₦{{ number_format($withdrawalStats['pending_amount'] / 100, 2) }} queued</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FEF9E7;color:#F39C12;flex-shrink:0;">
                        <i class="feather-upload"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">New Inquiries</p>
                        <h2 class="fw-bold mb-0 text-primary">{{ number_format($inquiryStats['new']) }}</h2>
                        <small class="text-muted">{{ $inquiryStats['converted'] }} converted total</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-mail"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Left: Recent Hotels + Tier Breakdown --}}
    <div class="col-lg-8">

        {{-- Tier breakdown --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Hotels by Tier</h5>
                <a href="{{ route('platform.hotels.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                @php
                    $tierConfig = [
                        'starter'    => ['bg-secondary', '#6C757D'],
                        'growth'     => ['bg-info',      '#17A2B8'],
                        'pro'        => ['bg-primary',   '#2980B9'],
                        'enterprise' => ['bg-success',   '#2ECC71'],
                    ];
                    $tierTotal = $byTier->sum();
                @endphp
                <div class="row g-3">
                    @foreach(['starter','growth','pro','enterprise'] as $t)
                    @php
                        $count = $byTier[$t] ?? 0;
                        $pct   = $tierTotal > 0 ? round($count / $tierTotal * 100) : 0;
                        [$badgeClass, $color] = $tierConfig[$t];
                    @endphp
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded text-center" style="background:#F8F9FA;">
                            <span class="badge {{ $badgeClass }} mb-2">{{ ucfirst($t) }}</span>
                            <div class="fw-bold fs-4">{{ $count }}</div>
                            <small class="text-muted">{{ $pct }}% of total</small>
                            <div class="progress mt-2" style="height:4px;">
                                <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent Hotels --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recently Joined Hotels</h5>
                <a href="{{ route('platform.hotels.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Hotel</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Tier</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Subscription</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Joined</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentHotels as $hotel)
                            @php
                                $tierBadge = match($hotel->tier) {
                                    'starter'    => 'bg-secondary',
                                    'growth'     => 'bg-info',
                                    'pro'        => 'bg-primary',
                                    'enterprise' => 'bg-success',
                                    default      => 'bg-secondary',
                                };
                                $subBadge = match($hotel->subscription_status) {
                                    'active'          => 'bg-success',
                                    'past_due'        => 'bg-warning text-dark',
                                    'pending_payment' => 'bg-secondary',
                                    'expired'         => 'bg-danger',
                                    default           => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('platform.hotels.show', $hotel->id) }}"
                                       class="fw-semibold text-primary text-decoration-none">{{ $hotel->name }}</a>
                                    <div class="text-muted fs-12">{{ $hotel->owner->name ?? '—' }}</div>
                                </td>
                                <td><span class="badge {{ $tierBadge }}">{{ ucfirst($hotel->tier) }}</span></td>
                                <td><span class="badge {{ $subBadge }}">{{ ucwords(str_replace('_', ' ', $hotel->subscription_status)) }}</span></td>
                                <td class="text-muted fs-12">{{ $hotel->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('platform.hotels.show', $hotel->id) }}"
                                       class="btn btn-sm btn-outline-primary"><i class="feather-eye"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- Right: Quick Links + Recent Activity --}}
    <div class="col-lg-4">

        {{-- Quick Links --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Quick Links</h5></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('platform.hotels.index') }}" class="btn btn-outline-dark btn-sm text-start">
                    <i class="feather-home me-2"></i> Browse Hotels
                </a>
                <a href="{{ route('platform.inquiries.index') }}" class="btn btn-outline-dark btn-sm text-start">
                    <i class="feather-mail me-2"></i> Enterprise Inquiries
                    @if($inquiryStats['new'] > 0)
                    <span class="badge bg-danger ms-1">{{ $inquiryStats['new'] }}</span>
                    @endif
                </a>
                @if(in_array($role, ['super_admin', 'finance']))
                <a href="{{ route('platform.revenue.index') }}" class="btn btn-outline-dark btn-sm text-start">
                    <i class="feather-bar-chart-2 me-2"></i> Revenue Reports
                </a>
                <a href="{{ route('platform.revenue.withdrawals') }}" class="btn btn-outline-dark btn-sm text-start">
                    <i class="feather-upload me-2"></i> Withdrawal Oversight
                    @if($withdrawalStats['pending_count'] > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $withdrawalStats['pending_count'] }}</span>
                    @endif
                </a>
                @endif
                @if($role === 'super_admin')
                <a href="{{ route('platform.admins.index') }}" class="btn btn-outline-dark btn-sm text-start">
                    <i class="feather-users me-2"></i> Manage Platform Admins
                </a>
                <a href="{{ route('platform.admins.activity-log') }}" class="btn btn-outline-dark btn-sm text-start">
                    <i class="feather-activity me-2"></i> Activity Log
                </a>
                @endif
            </div>
        </div>

        {{-- Recent Activity --}}
        @if($recentActivity->count())
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Activity</h5>
                @if($role === 'super_admin')
                <a href="{{ route('platform.admins.activity-log') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                @endif
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($recentActivity as $log)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex align-items-start gap-2">
                            <div class="avatar-text avatar-sm rounded-circle flex-shrink-0 mt-1"
                                 style="background:#EBF5FB;color:#2980B9;width:28px;height:28px;font-size:11px;">
                                {{ strtoupper(substr($log->admin->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <div class="fs-12 fw-semibold">{{ $log->admin->name ?? '—' }}</div>
                                <div class="fs-12 text-muted">{{ \Illuminate\Support\Str::limit($log->description, 60) }}</div>
                                <div class="fs-11 text-muted">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection