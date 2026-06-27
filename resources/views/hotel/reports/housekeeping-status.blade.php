@extends('layouts.hotel')
@section('title', 'Housekeeping Status')
@section('page_title', 'Housekeeping Status Board')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Housekeeping Status</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Room</th><th>Assigned To</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
                @foreach($tasks as $task)
                <tr>
                    <td>Room {{ $task->room->room_number }}</td>
                    <td>{{ $task->assignee->name ?? 'Unassigned' }}</td>
                    <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_',' ',$task->status) }}</span></td>
                    <td class="text-muted fs-13">{{ $task->created_at->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($tasks->isEmpty())<p class="text-muted text-center py-5 mb-0">No housekeeping tasks yet.</p>@endif
    </div>
</div>
@endsection
