@extends('layouts.hotel')
@section('title', 'Staff')
@section('page_title', 'Staff — ' . $location->name)
@section('breadcrumb')
    <li class="breadcrumb-item active">Staff</li>
@endsection

@section('content')

@if($allLocations->count() > 1)
<div class="mb-3">
    <label class="form-label fw-bold fs-13">Managing staff for:</label>
    <div class="btn-group d-flex flex-wrap" role="group">
        @foreach($allLocations as $loc)
        <a href="{{ route('hotel.staff.index', $loc) }}"
           class="btn btn-sm {{ $loc->id === $location->id ? 'btn-dark' : 'btn-outline-secondary' }}">
            {{ $loc->name }} @if($loc->isPrimaryLocation())<span class="badge bg-light text-dark ms-1">Primary</span>@endif
        </a>
        @endforeach
    </div>
</div>
@endif

<div class="alert {{ $canInviteMore ? 'alert-light border' : 'alert-warning' }} d-flex justify-content-between align-items-center mb-3">
    <span>
        <i class="feather-users me-2"></i>
        {{ $staffCount }} staff member(s) at {{ $location->name }} {{ $staffLimit ? "of {$staffLimit} allowed on your tier" : '(unlimited on your tier)' }}
    </span>
    @if(! $canInviteMore)
    <a href="{{ route('hotel.subscription.plans') }}" class="btn btn-sm btn-dark">Upgrade</a>
    @endif
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Invite Staff to {{ $location->name }}</h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.staff.invite', $location) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role</label>
                        <select name="role" class="form-select">
                            <option value="manager">Manager</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="cashier">Cashier</option>
                            <option value="housekeeper">Housekeeper</option>
                            <option value="room_service">Room Service</option>
                            <option value="accountant">Accountant</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100" {{ $canInviteMore ? '' : 'disabled' }}>
                        <i class="feather-send me-1"></i> Send Invite
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Staff List — {{ $location->name }}</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Role</th><th>Contact</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                        <tr>
                            <td class="fw-semibold">{{ $member->name }}</td>
                            <td class="text-capitalize">{{ str_replace('_',' ',$member->role) }}</td>
                            <td>{{ $member->email ?? $member->phone ?? '—' }}</td>
                            <td><span class="badge {{ $member->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $member->is_active ? 'Active' : 'Deactivated' }}</span></td>
                            <td>
                                @if($member->is_active)
                                <form action="{{ route('hotel.staff.deactivate', [$location, $member->id]) }}" method="POST" onsubmit="return confirm('Deactivate {{ $member->name }}?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                                </form>
                                @else
                                <form action="{{ route('hotel.staff.reactivate', [$location, $member->id]) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Reactivate</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No staff invited yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection