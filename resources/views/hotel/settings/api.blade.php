@extends('layouts.hotel')
@section('title', 'API Access')
@section('page_title', 'API Access')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.settings.index') }}">Settings</a></li>
    <li class="breadcrumb-item active">API</li>
@endsection

@section('content')

@if(session('newToken'))
<div class="alert alert-success">
    <strong>Copy this token now — it won't be shown again:</strong>
    <code class="d-block mt-2 p-2 bg-light rounded">{{ session('newToken') }}</code>
</div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Generate Token</h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.settings.api.generate') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Token name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Booking widget integration" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Generate Token</button>
                </form>
                <hr>
                <p class="text-muted fs-13 mb-1">Base URL: <code>{{ url('/api/v1') }}</code></p>
                <p class="text-muted fs-13 mb-1">Endpoints: <code>GET /rooms</code>, <code>GET /bookings</code></p>
                <p class="text-muted fs-13 mb-0">Auth header: <code>Authorization: Bearer &lt;token&gt;</code></p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Active Tokens</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @forelse($tokens as $token)
                        <tr>
                            <td>{{ $token->name }}</td>
                            <td class="text-muted fs-13">{{ $token->last_used_at?->diffForHumans() ?? 'Never used' }}</td>
                            <td>
                                <form action="{{ route('hotel.settings.api.revoke', $token->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Revoke</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No tokens yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
