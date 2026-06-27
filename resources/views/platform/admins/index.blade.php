@extends('layouts.platform.app')
@section('title', 'Platform Admins')
@section('page_title', 'Platform Admins')
@section('breadcrumb')
    <li class="breadcrumb-item active">Platform Admins</li>
@endsection

@section('content')

{{-- ── Stats ───────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Total Admins</p>
                        <h2 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h2>
                        <small class="text-muted">across all roles</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-users"></i>
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
                        <h2 class="fw-bold mb-0 text-success">{{ number_format($stats['active']) }}</h2>
                        <small class="text-muted">can log in</small>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Inactive</p>
                        <h2 class="fw-bold mb-0 text-secondary">{{ number_format($stats['inactive']) }}</h2>
                        <small class="text-muted">access revoked</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#ECF0F1;color:#95A5A6;flex-shrink:0;">
                        <i class="feather-x-circle"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Super Admins</p>
                        <h2 class="fw-bold mb-0 text-danger">{{ number_format($stats['by_role']['super_admin'] ?? 0) }}</h2>
                        <small class="text-muted">full access</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FADBD8;color:#E74C3C;flex-shrink:0;">
                        <i class="feather-shield"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="feather-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="feather-alert-triangle me-2"></i>{{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">

    {{-- ── Add Admin Form ──────────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Add Platform Admin</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-3" style="font-size:13px;">
                    <i class="feather-alert-triangle me-1"></i>
                    New admins can log in immediately. Share credentials securely.
                </div>

                <form action="{{ route('platform.admins.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-13">Name</label>
                        <input type="text" name="name"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-13">Email</label>
                        <input type="email" name="email"
                               class="form-control form-control-sm @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-13">Role</label>
                        <select name="role" class="form-select form-select-sm">
                            @foreach(['support' => 'Support','finance' => 'Finance','operations' => 'Operations','super_admin' => 'Super Admin'] as $val => $label)
                            <option value="{{ $val }}" {{ old('role') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text fs-11">
                            Support: view + impersonate. Finance: revenue + withdrawals. Operations: hotels + tier changes. Super Admin: everything.
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold fs-13">Temporary Password</label>
                        <input type="text" name="password"
                               class="form-control form-control-sm @error('password') is-invalid @enderror"
                               placeholder="Min 8 chars, at least one number" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm w-100">
                        <i class="feather-user-plus me-1"></i> Create Admin
                    </button>
                </form>
            </div>
        </div>

        {{-- Role breakdown mini-card --}}
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Admins by Role</h5></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach(['super_admin' => ['Super Admin','bg-danger'],'operations' => ['Operations','bg-primary'],'finance' => ['Finance','bg-success'],'support' => ['Support','bg-info']] as $role => [$label, $badge])
                    <li class="list-group-item d-flex align-items-center justify-content-between px-3 py-2">
                        <span class="fs-13">{{ $label }}</span>
                        <span class="badge {{ $badge }}">{{ $stats['by_role'][$role] ?? 0 }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- ── Admin Table ─────────────────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">All Platform Admins</h5>
                <a href="{{ route('platform.admins.activity-log') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="feather-activity me-1"></i> Activity Log
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Name</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Email</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Role</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                                <th class="fs-11 text-uppercase text-muted fw-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($admins as $admin)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $admin->name }}</span>
                                    @if($admin->id === auth('platform')->id())
                                    <span class="badge bg-secondary ms-1" style="font-size:10px;">You</span>
                                    @endif
                                </td>
                                <td class="text-muted fs-13">{{ $admin->email }}</td>
                                <td>
                                    <form action="{{ route('platform.admins.change-role', $admin->id) }}" method="POST">
                                        @csrf
                                        <select name="role"
                                                class="form-select form-select-sm"
                                                style="min-width:130px;"
                                                onchange="this.form.requestSubmit()"
                                                {{ $admin->id === auth('platform')->id() ? 'disabled' : '' }}>
                                            @foreach(['support' => 'Support','finance' => 'Finance','operations' => 'Operations','super_admin' => 'Super Admin'] as $val => $label)
                                            <option value="{{ $val }}" {{ $admin->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $admin->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @if($admin->id !== auth('platform')->id())
                                    <form action="{{ route('platform.admins.toggle-active', $admin->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm {{ $admin->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-muted fs-12">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection