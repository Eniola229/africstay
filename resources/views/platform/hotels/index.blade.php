@extends('layouts.platform.app')
@section('title', 'Hotels')
@section('page_title', 'All Hotels')

@section('content')
<form method="GET" class="d-flex gap-2 mb-3">
    <input type="text" name="search" value="{{ request('search') }}" class="form-control" style="max-width:300px;" placeholder="Search by name...">
    <select name="tier" class="form-select" style="max-width:180px;">
        <option value="">All tiers</option>
        @foreach(['starter','growth','pro','enterprise'] as $t)
        <option value="{{ $t }}" {{ request('tier') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
        @endforeach
    </select>
    <button class="btn btn-dark">Filter</button>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Hotel</th><th>Tier</th><th>Status</th><th>Subscription</th><th>Wallet</th><th>Joined</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach($hotels as $hotel)
                <tr>
                    <td>
                        <a href="{{ route('platform.hotels.show', $hotel->id) }}" class="fw-semibold text-dark text-decoration-none">{{ $hotel->name }}</a>
                        <div class="text-muted fs-12">{{ $hotel->owner->name ?? '—' }}</div>
                    </td>
                    <td class="text-capitalize">{{ $hotel->tier }}</td>
                    <td><span class="badge {{ $hotel->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $hotel->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td><span class="badge {{ match($hotel->subscription_status) { 'active' => 'bg-success', 'past_due' => 'bg-warning text-dark', 'pending_payment' => 'bg-secondary', default => 'bg-danger' } }}">{{ str_replace('_',' ',$hotel->subscription_status) }}</span></td>
                    <td>₦{{ number_format($hotel->walletBalanceNaira(), 2) }}</td>
                    <td class="text-muted fs-13">{{ $hotel->created_at->format('d M Y') }}</td>
                    <td><a href="{{ route('platform.hotels.show', $hotel->id) }}" class="btn btn-sm btn-outline-dark">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $hotels->links() }}</div>
    </div>
</div>
@endsection
