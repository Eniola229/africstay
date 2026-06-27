@extends('layouts.platform.app')
@section('title', 'Withdrawal Oversight')
@section('page_title', 'Withdrawal Oversight')

@section('content')
<div class="d-flex gap-2 mb-3">
    @foreach(['all','pending','processing','completed','failed'] as $tab)
    <a href="{{ route('platform.revenue.withdrawals', $tab === 'all' ? [] : ['status' => $tab]) }}"
       class="btn btn-sm {{ $currentStatus === $tab ? 'btn-dark' : 'btn-outline-secondary' }}">{{ ucfirst($tab) }}</a>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Hotel</th><th>Amount</th><th>Bank</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($withdrawals as $w)
                <tr>
                    <td>{{ $w->hotel->name }}</td>
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
