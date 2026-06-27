@extends('layouts.platform.app')
@section('title', $hotel->name)
@section('page_title', $hotel->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('platform.hotels.index') }}">Hotels</a></li>
    <li class="breadcrumb-item active">{{ $hotel->name }}</li>
@endsection

@section('content')
@php $role = auth('platform')->user()->role; @endphp

{{-- ── Stat cards ──────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Wallet Balance</p>
                        <h2 class="fw-bold mb-0 text-success">₦{{ number_format($hotel->walletBalanceNaira(), 2) }}</h2>
                        <small class="text-muted">available balance</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;flex-shrink:0;">
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Tier</p>
                        <h2 class="fw-bold mb-0 text-capitalize">{{ $hotel->tier }}</h2>
                        <small class="text-muted">current plan</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-layers"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Subscription</p>
                        <h2 class="fw-bold mb-0 text-capitalize" style="font-size:1.1rem;">
                            {{ ucwords(str_replace('_', ' ', $hotel->subscription_status)) }}
                        </h2>
                        @if($hotel->subscription_ends_at)
                        <small class="text-muted">ends {{ $hotel->subscription_ends_at->format('d M Y') }}</small>
                        @endif
                    </div>
                    @php
                        $subColor = match($hotel->subscription_status) {
                            'active'          => ['#D5F5E3','#2ECC71'],
                            'past_due'        => ['#FEF9E7','#F39C12'],
                            'pending_payment' => ['#ECF0F1','#95A5A6'],
                            default           => ['#FADBD8','#E74C3C'],
                        };
                    @endphp
                    <div class="avatar-text avatar-lg rounded flex-shrink:0"
                         style="background:{{ $subColor[0] }};color:{{ $subColor[1] }};flex-shrink:0;">
                        <i class="feather-credit-card"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Locations</p>
                        <h2 class="fw-bold mb-0">{{ $hotel->childLocations->count() + 1 }}</h2>
                        <small class="text-muted">total locations</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#F4ECF7;color:#8E44AD;flex-shrink:0;">
                        <i class="feather-map-pin"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">

    {{-- Left column --}}
    <div class="col-lg-8">

        {{-- Hotel Info --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Hotel Info</h5>
                <span class="badge {{ $hotel->is_active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $hotel->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size:11px;">Owner</small>
                        <span class="fw-semibold">{{ $hotel->owner->name ?? '—' }}</span>
                        <span class="text-muted fs-13"> · {{ $hotel->owner->email ?? $hotel->owner->phone ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size:11px;">Phone</small>
                        <span>{{ $hotel->phone }}</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size:11px;">Email</small>
                        <span>{{ $hotel->email ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size:11px;">Address</small>
                        <span>{{ $hotel->address }}, {{ $hotel->city }}, {{ $hotel->state }}</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size:11px;">Joined</small>
                        <span>{{ $hotel->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size:11px;">Onboarding</small>
                        <span>Step {{ $hotel->onboarding_step }}
                            @if($hotel->onboarding_completed)
                            <span class="badge bg-success ms-1">Completed</span>
                            @else
                            <span class="badge bg-warning text-dark ms-1">In Progress</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Payments --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Payments</h5>
                <span class="badge bg-secondary">{{ $payments->count() }} records</span>
            </div>
            <div class="card-body p-0">
                @if($payments->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Reference</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Amount</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $p)
                            <tr>
                                <td><code class="fs-12">{{ $p->payment_reference }}</code></td>
                                <td class="fw-bold">₦{{ number_format($p->amountNaira(), 2) }}</td>
                                <td>
                                    <span class="badge {{ match($p->status) {
                                        'confirmed' => 'bg-success',
                                        'pending'   => 'bg-warning text-dark',
                                        'failed'    => 'bg-danger',
                                        default     => 'bg-secondary',
                                    } }}">{{ ucfirst($p->status) }}</span>
                                </td>
                                <td class="text-muted fs-12">{{ $p->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="feather-credit-card d-block mb-2" style="font-size:32px;"></i>
                    No payments yet.
                </div>
                @endif
            </div>
        </div>

        {{-- Recent Withdrawals --}}
        @if($withdrawals->count())
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Withdrawals</h5>
                <span class="badge bg-secondary">{{ $withdrawals->count() }} records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Amount</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Bank</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawals as $w)
                            <tr>
                                <td class="fw-bold">₦{{ number_format($w->amountNaira(), 2) }}</td>
                                <td class="fs-13 text-muted">{{ $w->bank_name ?? '—' }} · {{ $w->account_number ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ match($w->status) {
                                        'completed'  => 'bg-success',
                                        'processing' => 'bg-info',
                                        'pending'    => 'bg-warning text-dark',
                                        default      => 'bg-danger',
                                    } }}">{{ ucfirst($w->status) }}</span>
                                </td>
                                <td class="text-muted fs-12">{{ $w->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Right column --}}
    <div class="col-lg-4">

        {{-- Actions --}}
        @if(in_array($role, ['super_admin','operations']))
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Admin Actions</h5></div>
            <div class="card-body">
                <div class="alert alert-warning mb-3" style="font-size:13px;">
                    <i class="feather-alert-triangle me-1"></i>
                    These actions are logged in the activity log.
                </div>

                <form action="{{ route('platform.hotels.toggle-active', $hotel->id) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit"
                            class="btn {{ $hotel->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} w-100">
                        <i class="feather-{{ $hotel->is_active ? 'x-circle' : 'check-circle' }} me-2"></i>
                        {{ $hotel->is_active ? 'Deactivate Hotel' : 'Activate Hotel' }}
                    </button>
                </form>

                <hr>

                <form action="{{ route('platform.hotels.change-tier', $hotel->id) }}" method="POST">
                    @csrf
                    <label class="form-label fw-semibold fs-13">Change Tier</label>
                    <select name="tier" class="form-select form-select-sm mb-2">
                        @foreach(['starter','growth','pro','enterprise'] as $t)
                        <option value="{{ $t }}" {{ $hotel->tier === $t ? 'selected' : '' }}>
                            {{ ucfirst($t) }}
                        </option>
                        @endforeach
                    </select>
                    <input type="text" name="reason"
                           class="form-control form-control-sm mb-2 @error('reason') is-invalid @enderror"
                           placeholder="Reason (required — this is logged)" required>
                    @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-dark btn-sm w-100">
                        <i class="feather-refresh-cw me-1"></i> Change Tier
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Impersonate --}}
        @if(in_array($role, ['super_admin','support']))
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Impersonate</h5></div>
            <div class="card-body">
                <p class="text-muted fs-13 mb-3">
                    View this hotel's dashboard exactly as they see it —
                    read-only, watermarked, every write action blocked.
                </p>
                <form action="{{ route('platform.hotels.impersonate', $hotel->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-dark w-100">
                        <i class="feather-eye me-2"></i> Impersonate (Read-Only)
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Subscription history --}}
        @if($hotel->subscriptions->count())
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Subscription History</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Tier</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Ends</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hotel->subscriptions->sortByDesc('created_at')->take(5) as $sub)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary text-capitalize">{{ $sub->tier }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ match($sub->status) {
                                        'active'    => 'bg-success',
                                        'past_due'  => 'bg-warning text-dark',
                                        'expired'   => 'bg-danger',
                                        'cancelled' => 'bg-danger',
                                        default     => 'bg-secondary',
                                    } }}">{{ ucfirst($sub->status) }}</span>
                                </td>
                                <td class="text-muted fs-12">
                                    {{ $sub->ends_at?->format('d M Y') ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection