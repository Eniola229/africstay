@extends('layouts.platform.app')
@section('title', 'Withdrawal Oversight')
@section('page_title', 'Withdrawal Oversight')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('platform.revenue.index') }}">Revenue Reports</a></li>
    <li class="breadcrumb-item active">Withdrawal Oversight</li>
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
                        <i class="feather-upload"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Pending</p>
                        <h2 class="fw-bold mb-0 text-warning">{{ number_format($stats['pending']) }}</h2>
                        <small class="text-muted">₦{{ number_format($stats['pending_amount'] / 100, 2) }}</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FEF9E7;color:#F39C12;flex-shrink:0;">
                        <i class="feather-clock"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Processing</p>
                        <h2 class="fw-bold mb-0 text-info">{{ number_format($stats['processing']) }}</h2>
                        <small class="text-muted">₦{{ number_format($stats['processing_amount'] / 100, 2) }}</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#D6EAF8;color:#2980B9;flex-shrink:0;">
                        <i class="feather-loader"></i>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Completed</p>
                        <h2 class="fw-bold mb-0 text-success">{{ number_format($stats['completed']) }}</h2>
                        <small class="text-muted">paid out</small>
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
                        <p class="text-muted fs-12 fw-semibold text-uppercase mb-1">Failed</p>
                        <h2 class="fw-bold mb-0 text-danger">{{ number_format($stats['failed']) }}</h2>
                        <small class="text-muted">need review</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded" style="background:#FADBD8;color:#E74C3C;flex-shrink:0;">
                        <i class="feather-alert-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card h-100" style="background:#FEF9E7;">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="fw-semibold text-uppercase mb-1" style="font-size:12px;color:#7D6608;">Queued Value</p>
                        <h2 class="fw-bold mb-0" style="color:#7D6608;">
                            ₦{{ number_format(($stats['pending_amount'] + $stats['processing_amount']) / 100, 2) }}
                        </h2>
                        <small style="color:#9A7D0A;">pending + processing</small>
                    </div>
                    <div class="avatar-text avatar-lg rounded flex-shrink-0" style="background:#FCF3CF;color:#7D6608;">
                        <i class="feather-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('platform.revenue.withdrawals') }}"
              class="d-flex align-items-center gap-3 flex-wrap">

            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" style="max-width:220px;"
                   placeholder="Search by hotel name…">

            <span class="fw-semibold fs-13 text-muted me-1">Status:</span>
            <div class="btn-group">
                <a href="{{ route('platform.revenue.withdrawals', array_merge(request()->except('status','page'), [])) }}"
                   class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-outline-secondary' }}">All</a>
                @foreach(['pending' => 'btn-warning', 'processing' => 'btn-info', 'completed' => 'btn-success', 'failed' => 'btn-danger'] as $s => $btnColor)
                <a href="{{ route('platform.revenue.withdrawals', array_merge(request()->except('status','page'), ['status' => $s])) }}"
                   class="btn btn-sm {{ request('status') === $s ? $btnColor : 'btn-outline-secondary' }}">
                    {{ ucfirst($s) }}
                </a>
                @endforeach
            </div>

            @if(request()->anyFilled(['search','status']))
            <a href="{{ route('platform.revenue.withdrawals') }}" class="btn btn-sm btn-outline-secondary">
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
        @if($withdrawals->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Hotel</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Amount</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Bank Details</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Provider</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($withdrawals as $w)
                    @php
                        $statusBadge = match($w->status) {
                            'completed'  => 'bg-success',
                            'processing' => 'bg-info',
                            'pending'    => 'bg-warning text-dark',
                            'failed'     => 'bg-danger',
                            default      => 'bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('platform.hotels.show', $w->hotel->id) }}"
                               class="fw-semibold text-primary text-decoration-none">
                                {{ $w->hotel->name }}
                            </a>
                            <div class="text-muted fs-12 text-capitalize">{{ $w->hotel->tier ?? '—' }}</div>
                        </td>
                        <td class="fw-bold">₦{{ number_format($w->amountNaira(), 2) }}</td>
                        <td>
                            <span class="fw-semibold fs-13">{{ $w->account_number }}</span>
                            <div class="text-muted fs-12">{{ $w->bank_name ?? '—' }} · {{ $w->account_name ?? '—' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark text-capitalize">{{ $w->provider }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $statusBadge }}">{{ ucfirst($w->status) }}</span>
                            @if($w->failure_reason)
                            <div class="text-danger fs-11 mt-1">{{ $w->failure_reason }}</div>
                            @endif
                        </td>
                        <td class="text-muted fs-12">
                            {{ $w->created_at->format('d M Y') }}
                            @if($w->processed_at)
                            <div class="fs-11 text-success">Done {{ $w->processed_at->format('d M') }}</div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $withdrawals->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-upload d-block mb-2" style="font-size:40px;"></i>
            <p>No withdrawals found.</p>
        </div>
        @endif
    </div>
</div>

@endsection