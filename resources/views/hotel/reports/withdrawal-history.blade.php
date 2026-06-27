@extends('layouts.hotel')
@section('title', 'Withdrawal History')
@section('page_title', 'Withdrawal History')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Withdrawal History</li>
@endsection
@section('page_actions')
    <a href="{{ route('hotel.reports.export.csv', 'withdrawal-history') }}" class="btn btn-sm btn-outline-secondary"><i class="feather-download me-1"></i> CSV</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Reference</th><th>Amount</th><th>Bank</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($withdrawals as $w)
                <tr>
                    <td>{{ $w->reference }}</td>
                    <td class="fw-bold">₦{{ number_format($w->amountNaira(), 2) }}</td>
                    <td>{{ $w->account_number }} ({{ $w->bank_name }})</td>
                    <td><span class="badge {{ match($w->status) { 'completed' => 'bg-success', 'processing' => 'bg-info text-white', 'pending' => 'bg-secondary', default => 'bg-danger' } }}">{{ ucfirst($w->status) }}</span></td>
                    <td class="text-muted fs-13">{{ $w->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $withdrawals->links() }}</div>
    </div>
</div>
@endsection
