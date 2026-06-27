@extends('layouts.platform.app')
@section('title', 'Hotels')
@section('page_title', 'All Hotels')
@section('breadcrumb')
    <li class="breadcrumb-item active">Hotels</li>
@endsection

@section('content')

{{-- ── Stats ───────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Total Hotels</p>
                        <h2 class="fw-bold mb-0">{{ number_format($hotels->total()) }}</h2>
                        <small class="text-muted">across all tiers</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Active</p>
                        <h2 class="fw-bold mb-0 text-success">{{ number_format($countByStatus['active'] ?? 0) }}</h2>
                        <small class="text-muted">active subscriptions</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;flex-shrink:0;">
                        <i class="feather-check-circle"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Pending Payment</p>
                        <h2 class="fw-bold mb-0 text-warning">{{ number_format($countByStatus['pending_payment'] ?? 0) }}</h2>
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
                        <h2 class="fw-bold mb-0 text-danger">{{ number_format(($countByStatus['past_due'] ?? 0) + ($countByStatus['expired'] ?? 0)) }}</h2>
                        <small class="text-muted">need attention</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FADBD8;color:#E74C3C;flex-shrink:0;">
                        <i class="feather-alert-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('platform.hotels.index') }}" class="d-flex align-items-center gap-3 flex-wrap">

            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" style="max-width:240px;"
                   placeholder="Search by name...">

            <span class="fw-semibold fs-13 text-muted me-1">Tier:</span>
            <div class="btn-group">
                <a href="{{ route('platform.hotels.index', array_merge(request()->except('tier','page'), [])) }}"
                   class="btn btn-sm {{ !request('tier') ? 'btn-dark' : 'btn-outline-secondary' }}">All</a>
                @foreach(['starter','growth','pro','enterprise'] as $t)
                <a href="{{ route('platform.hotels.index', array_merge(request()->except('page'), ['tier' => $t])) }}"
                   class="btn btn-sm {{ request('tier') === $t ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ ucfirst($t) }}
                </a>
                @endforeach
            </div>

            <span class="fw-semibold fs-13 text-muted ms-2 me-1">Status:</span>
            <div class="btn-group">
                @foreach(['active' => 'btn-success', 'pending_payment' => 'btn-warning', 'past_due' => 'btn-danger', 'expired' => 'btn-danger'] as $s => $btnColor)
                @php $isActive = request('subscription_status') === $s; @endphp
                <a href="{{ route('platform.hotels.index', array_merge(request()->except('subscription_status','page'), ['subscription_status' => $s])) }}"
                   class="btn btn-sm {{ $isActive ? $btnColor : 'btn-outline-secondary' }}">
                    {{ ucwords(str_replace('_', ' ', $s)) }}
                </a>
                @endforeach
            </div>

            @if(request()->anyFilled(['search','tier','subscription_status']))
            <a href="{{ route('platform.hotels.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="feather-x me-1"></i> Clear
            </a>
            @endif

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="feather-search me-1"></i> Search
            </button>

        </form>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-body p-0">
        @if($hotels->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Hotel</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Tier</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Active</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Subscription</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Wallet</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Joined</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hotels as $hotel)
                    <tr>
                        <td>
                            <a href="{{ route('platform.hotels.show', $hotel->id) }}"
                               class="fw-semibold text-primary text-decoration-none">
                                {{ $hotel->name }}
                            </a>
                            <div class="text-muted fs-12">{{ $hotel->owner->name ?? '—' }}</div>
                        </td>
                        <td>
                            @php
                                $tierBadge = match($hotel->tier) {
                                    'starter'    => 'bg-secondary',
                                    'growth'     => 'bg-info',
                                    'pro'        => 'bg-primary',
                                    'enterprise' => 'bg-success',
                                    default      => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $tierBadge }}">{{ ucfirst($hotel->tier) }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $hotel->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $hotel->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $subBadge = match($hotel->subscription_status) {
                                    'active'          => 'bg-success',
                                    'past_due'        => 'bg-warning text-dark',
                                    'pending_payment' => 'bg-secondary',
                                    'expired'         => 'bg-danger',
                                    'cancelled'       => 'bg-danger',
                                    default           => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $subBadge }}">
                                {{ ucwords(str_replace('_', ' ', $hotel->subscription_status)) }}
                            </span>
                        </td>
                        <td class="fw-semibold">₦{{ number_format($hotel->walletBalanceNaira(), 2) }}</td>
                        <td class="text-muted fs-12">{{ $hotel->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('platform.hotels.show', $hotel->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="feather-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $hotels->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-home mb-2 d-block" style="font-size:40px;"></i>
            <p>No hotels found.</p>
        </div>
        @endif
    </div>
</div>

@endsection