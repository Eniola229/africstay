@extends('layouts.platform.app')
@section('title', $hotel->name)
@section('page_title', $hotel->name)

@section('content')
@php $role = auth('platform')->user()->role; @endphp

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Hotel Info</h5></div>
            <div class="card-body">
                <div class="row mb-2"><div class="col-4 text-muted">Owner</div><div class="col-8">{{ $hotel->owner->name ?? '—' }} ({{ $hotel->owner->email ?? $hotel->owner->phone ?? '—' }})</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Phone</div><div class="col-8">{{ $hotel->phone }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Email</div><div class="col-8">{{ $hotel->email ?? '—' }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Address</div><div class="col-8">{{ $hotel->address }}, {{ $hotel->city }}, {{ $hotel->state }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Tier</div><div class="col-8 text-capitalize">{{ $hotel->tier }}</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Subscription</div><div class="col-8 text-capitalize">{{ str_replace('_',' ',$hotel->subscription_status) }} @if($hotel->subscription_ends_at) (ends {{ $hotel->subscription_ends_at->format('d M Y') }}) @endif</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Wallet</div><div class="col-8 fw-bold">₦{{ number_format($hotel->walletBalanceNaira(), 2) }}</div></div>
                @if($hotel->childLocations->count())
                <div class="row"><div class="col-4 text-muted">Locations</div><div class="col-8">{{ $hotel->childLocations->count() + 1 }} total</div></div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Recent Payments</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($payments as $p)
                        <tr><td>{{ $p->payment_reference }}</td><td>₦{{ number_format($p->amountNaira(), 2) }}</td><td><span class="badge bg-light text-dark">{{ $p->status }}</span></td><td class="text-muted fs-13">{{ $p->created_at->format('d M Y') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                @if($payments->isEmpty())<p class="text-muted text-center py-3 mb-0">No payments yet.</p>@endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if(in_array($role, ['super_admin','operations']))
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Actions</h5></div>
            <div class="card-body">
                <form action="{{ route('platform.hotels.toggle-active', $hotel->id) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn {{ $hotel->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} w-100">
                        {{ $hotel->is_active ? 'Deactivate Hotel' : 'Activate Hotel' }}
                    </button>
                </form>

                <form action="{{ route('platform.hotels.change-tier', $hotel->id) }}" method="POST">
                    @csrf
                    <label class="form-label fw-bold">Change Tier</label>
                    <select name="tier" class="form-select mb-2">
                        @foreach(['starter','growth','pro','enterprise'] as $t)
                        <option value="{{ $t }}" {{ $hotel->tier === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="reason" class="form-control mb-2 @error('reason') is-invalid @enderror" placeholder="Reason (required, logged)" required>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-dark w-100">Change Tier</button>
                </form>
            </div>
        </div>
        @endif

        @if(in_array($role, ['super_admin','support']))
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Impersonate</h5></div>
            <div class="card-body">
                <p class="text-muted fs-13">View this hotel's dashboard exactly as they see it — read-only, watermarked, every action blocked.</p>
                <form action="{{ route('platform.hotels.impersonate', $hotel->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-dark w-100"><i class="feather-eye me-1"></i> Impersonate (Read-Only)</button>
                </form>
            </div>
        </div>
        @endif

        @if($withdrawals->count())
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Recent Withdrawals</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($withdrawals as $w)
                        <tr><td>₦{{ number_format($w->amountNaira(), 2) }}</td><td><span class="badge bg-light text-dark">{{ $w->status }}</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
