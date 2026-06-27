@extends('layouts.platform.app')
@section('title', 'Activity Log')
@section('page_title', 'Platform Activity Log')

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Admin</th><th>Action</th><th>Description</th><th>IP</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->admin->name ?? '—' }}<div class="text-muted fs-12 text-capitalize">{{ $log->role }}</div></td>
                    <td><span class="badge bg-light text-dark">{{ $log->action }}</span></td>
                    <td class="fs-13">{{ $log->description }}</td>
                    <td class="text-muted fs-12">{{ $log->ip_address }}</td>
                    <td class="text-muted fs-13">{{ $log->created_at->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
