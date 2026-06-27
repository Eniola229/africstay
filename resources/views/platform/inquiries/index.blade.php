@extends('layouts.platform.app')
@section('title', 'Enterprise Inquiries')
@section('page_title', 'Enterprise Inquiries')

@section('content')
<div class="d-flex gap-2 mb-3">
    @foreach(['all','new','contacted','converted','closed'] as $tab)
    <a href="{{ route('platform.inquiries.index', $tab === 'all' ? [] : ['status' => $tab]) }}"
       class="btn btn-sm {{ $currentStatus === $tab ? 'btn-dark' : 'btn-outline-secondary' }}">{{ ucfirst($tab) }}</a>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Contact</th><th>Hotel</th><th>Message</th><th>Assigned</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($inquiries as $inquiry)
                <tr>
                    <td>{{ $inquiry->contact_name }}<div class="text-muted fs-12">{{ $inquiry->email ?? $inquiry->phone }}</div></td>
                    <td>{{ $inquiry->hotel_name }}</td>
                    <td class="fs-13">{{ \Illuminate\Support\Str::limit($inquiry->message, 60) }}</td>
                    <td>
                        <form action="{{ route('platform.inquiries.assign', $inquiry->id) }}" method="POST">
                            @csrf
                            <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                                <option value="">Unassigned</option>
                                @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ $inquiry->assigned_to === $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        <form action="{{ route('platform.inquiries.update-status', $inquiry->id) }}" method="POST">
                            @csrf
                            <select name="status" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                                @foreach(['new','contacted','converted','closed'] as $s)
                                <option value="{{ $s }}" {{ $inquiry->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="text-muted fs-13">{{ $inquiry->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $inquiries->links() }}</div>
    </div>
</div>
@endsection
