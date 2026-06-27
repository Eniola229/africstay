@extends('layouts.platform.app')
@section('title', 'Enterprise Inquiries')
@section('page_title', 'Enterprise Inquiries')
@section('breadcrumb')
    <li class="breadcrumb-item active">Enterprise Inquiries</li>
@endsection

@section('content')

{{-- ── Stats ───────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-md-2 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Total</p>
                        <h2 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h2>
                        <small class="text-muted">all time</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#EBF5FB;color:#2980B9;flex-shrink:0;">
                        <i class="feather-mail"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">New</p>
                        <h2 class="fw-bold mb-0 text-danger">{{ number_format($stats['new']) }}</h2>
                        <small class="text-muted">need action</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FADBD8;color:#E74C3C;flex-shrink:0;">
                        <i class="feather-alert-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Contacted</p>
                        <h2 class="fw-bold mb-0 text-warning">{{ number_format($stats['contacted']) }}</h2>
                        <small class="text-muted">in progress</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FEF9E7;color:#F39C12;flex-shrink:0;">
                        <i class="feather-phone"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Converted</p>
                        <h2 class="fw-bold mb-0 text-success">{{ number_format($stats['converted']) }}</h2>
                        <small class="text-muted">signed up</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D5F5E3;color:#2ECC71;flex-shrink:0;">
                        <i class="feather-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Closed</p>
                        <h2 class="fw-bold mb-0 text-secondary">{{ number_format($stats['closed']) }}</h2>
                        <small class="text-muted">not converting</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#ECF0F1;color:#95A5A6;flex-shrink:0;">
                        <i class="feather-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Unassigned</p>
                        <h2 class="fw-bold mb-0 text-danger">{{ number_format($stats['unassigned']) }}</h2>
                        <small class="text-muted">new & no owner</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FADBD8;color:#E74C3C;flex-shrink:0;">
                        <i class="feather-user-x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('platform.inquiries.index') }}"
              class="d-flex align-items-center gap-3 flex-wrap">

            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" style="max-width:240px;"
                   placeholder="Search name, hotel, email…">

            <span class="fw-semibold fs-13 text-muted me-1">Status:</span>
            <div class="btn-group">
                <a href="{{ route('platform.inquiries.index', array_merge(request()->except('status','page'), [])) }}"
                   class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-outline-secondary' }}">All</a>
                @foreach(['new' => 'btn-danger', 'contacted' => 'btn-warning', 'converted' => 'btn-success', 'closed' => 'btn-secondary'] as $s => $btnColor)
                <a href="{{ route('platform.inquiries.index', array_merge(request()->except('status','page'), ['status' => $s])) }}"
                   class="btn btn-sm {{ request('status') === $s ? $btnColor : 'btn-outline-secondary' }}">
                    {{ ucfirst($s) }}
                </a>
                @endforeach
            </div>

            @if(request()->anyFilled(['search','status']))
            <a href="{{ route('platform.inquiries.index') }}" class="btn btn-sm btn-outline-secondary">
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
        @if($inquiries->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Contact</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Hotel</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Message</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Assigned To</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inquiries as $inquiry)
                    @php
                        $statusBadge = match($inquiry->status) {
                            'new'       => 'bg-danger',
                            'contacted' => 'bg-warning text-dark',
                            'converted' => 'bg-success',
                            'closed'    => 'bg-secondary',
                            default     => 'bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $inquiry->contact_name }}</span>
                            <div class="text-muted fs-12">{{ $inquiry->email ?? $inquiry->phone ?? '—' }}</div>
                        </td>
                        <td class="fw-semibold fs-13">{{ $inquiry->hotel_name }}</td>
                        <td class="fs-12 text-muted" style="max-width:200px;">
                            {{ \Illuminate\Support\Str::limit($inquiry->message, 70) }}
                        </td>
                        <td>
                            <form action="{{ route('platform.inquiries.assign', $inquiry->id) }}" method="POST">
                                @csrf
                                <select name="assigned_to"
                                        class="form-select form-select-sm"
                                        style="min-width:150px;"
                                        onchange="this.form.requestSubmit()">
                                    <option value="">Unassigned</option>
                                    @foreach($admins as $admin)
                                    <option value="{{ $admin->id }}"
                                            {{ $inquiry->assigned_to === $admin->id ? 'selected' : '' }}>
                                        {{ $admin->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('platform.inquiries.update-status', $inquiry->id) }}" method="POST">
                                @csrf
                                <select name="status"
                                        class="form-select form-select-sm"
                                        style="min-width:130px;"
                                        onchange="this.form.requestSubmit()">
                                    @foreach(['new','contacted','converted','closed'] as $s)
                                    <option value="{{ $s }}" {{ $inquiry->status === $s ? 'selected' : '' }}>
                                        {{ ucfirst($s) }}
                                    </option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-muted fs-12">{{ $inquiry->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $inquiries->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-mail d-block mb-2" style="font-size:40px;"></i>
            <p>No inquiries found.</p>
        </div>
        @endif
    </div>
</div>

@endsection