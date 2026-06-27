@extends('layouts.platform.app')
@section('title', 'Activity Log')
@section('page_title', 'Platform Activity Log')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('platform.admins.index') }}">Platform Admins</a></li>
    <li class="breadcrumb-item active">Activity Log</li>
@endsection

@section('content')

{{-- ── Stats ───────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-3 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Today's Actions</p>
                        <h2 class="fw-bold mb-0">{{ number_format($logStats['total_today']) }}</h2>
                        <small class="text-muted">logged so far today</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-activity"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Last 7 Days</p>
                        <h2 class="fw-bold mb-0">{{ number_format($logStats['total_week']) }}</h2>
                        <small class="text-muted">total actions</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;flex-shrink:0;">
                        <i class="feather-calendar"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Active Admins</p>
                        <h2 class="fw-bold mb-0 text-primary">{{ number_format($logStats['unique_admins']) }}</h2>
                        <small class="text-muted">in the last 7 days</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#F4ECF7;color:#8E44AD;flex-shrink:0;">
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Action Types</p>
                        <h2 class="fw-bold mb-0">{{ count($logStats['action_types']) }}</h2>
                        <small class="text-muted">distinct actions tracked</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FEF9E7;color:#F39C12;flex-shrink:0;">
                        <i class="feather-list"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('platform.admins.activity-log') }}"
              class="d-flex align-items-center gap-3 flex-wrap">

            <select name="action" class="form-select form-select-sm" style="max-width:220px;">
                <option value="">All Action Types</option>
                @foreach($logStats['action_types'] as $action)
                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                    {{ $action }}
                </option>
                @endforeach
            </select>

            <select name="role" class="form-select form-select-sm" style="max-width:180px;">
                <option value="">All Roles</option>
                @foreach(['super_admin','operations','finance','support'] as $r)
                <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $r)) }}
                </option>
                @endforeach
            </select>

            <div class="d-flex align-items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}"
                       class="form-control form-control-sm" style="max-width:150px;" placeholder="From">
                <span class="text-muted fs-12">to</span>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="form-control form-control-sm" style="max-width:150px;" placeholder="To">
            </div>

            @if(request()->anyFilled(['action','role','from','to']))
            <a href="{{ route('platform.admins.activity-log') }}" class="btn btn-sm btn-outline-secondary">
                <i class="feather-x me-1"></i> Clear
            </a>
            @endif

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="feather-search me-1"></i> Filter
            </button>

        </form>
    </div>
</div>

{{-- ── Log Table ────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-body p-0">
        @if($logs->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Admin</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Action</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Target</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Description</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">IP</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php
                        $actionColor = match(true) {
                            str_contains($log->action, 'DEACTIVATE') || str_contains($log->action, 'DELETE') => 'bg-danger',
                            str_contains($log->action, 'ACTIVATE')   || str_contains($log->action, 'CREATE') => 'bg-success',
                            str_contains($log->action, 'IMPERSONATE')                                        => 'bg-warning text-dark',
                            str_contains($log->action, 'CHANGE')                                             => 'bg-info',
                            default                                                                           => 'bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $log->admin->name ?? '—' }}</span>
                            <div class="text-muted fs-12 text-capitalize">{{ str_replace('_', ' ', $log->role ?? '') }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $actionColor }}" style="font-size:10px;">{{ $log->action }}</span>
                        </td>
                        <td class="fs-12 text-muted">
                            @if($log->subject_type && $log->subject_name)
                            <span>{{ $log->subject_type }}</span>
                            <div class="fw-semibold text-dark">{{ $log->subject_name }}</div>
                            @else
                            —
                            @endif
                        </td>
                        <td class="fs-13" style="max-width:260px;">
                            {{ $log->description }}
                        </td>
                        <td class="text-muted fs-12">{{ $log->ip_address ?? '—' }}</td>
                        <td class="text-muted fs-12">
                            {{ $log->created_at->format('d M Y') }}
                            <div class="fs-11">{{ $log->created_at->format('H:i') }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $logs->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-activity d-block mb-2" style="font-size:40px;"></i>
            <p>No activity logs found.</p>
        </div>
        @endif
    </div>
</div>

@endsection