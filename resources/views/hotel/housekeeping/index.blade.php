@extends('layouts.hotel')
@section('title', 'Housekeeping')
@section('page_title', 'Housekeeping')
@section('breadcrumb')
    <li class="breadcrumb-item active">Housekeeping</li>
@endsection

@section('content')

@if($tasks->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="feather-clipboard mb-2 d-block" style="font-size:40px;"></i>
    No housekeeping tasks right now.
</div>
@else
<div class="row g-3">
    @foreach($tasks as $task)
    @php
        $badge = match($task->status) {
            'pending' => 'bg-secondary', 'in_progress' => 'bg-warning text-dark',
            'cleaned' => 'bg-info text-white', 'verified' => 'bg-success', default => 'bg-secondary',
        };
        $checklist = $task->checklist ?? [];
        $doneCount = count(array_filter($checklist, fn($c) => $c['done'] ?? false));
    @endphp
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Room {{ $task->room->room_number }}</h6>
                <span class="badge {{ $badge }} text-capitalize">{{ str_replace('_',' ',$task->status) }}</span>
            </div>
            <div class="card-body">
                <p class="text-muted fs-13 mb-2">{{ $doneCount }}/{{ count($checklist) }} checklist items done</p>
                <form action="{{ route('hotel.housekeeping.checklist', $task->id) }}" method="POST" class="checklist-form">
                    @csrf
                    @foreach($checklist as $i => $item)
                    <div class="form-check mb-1">
                        <input type="hidden" name="checklist[{{ $i }}][label]" value="{{ $item['label'] }}">
                        <input type="checkbox" class="form-check-input" name="checklist[{{ $i }}][done]" value="1"
                               {{ ($item['done'] ?? false) ? 'checked' : '' }} onchange="this.form.requestSubmit()"
                               {{ $task->status === 'verified' ? 'disabled' : '' }}>
                        <label class="form-check-label fs-13 {{ ($item['done'] ?? false) ? 'text-decoration-line-through text-muted' : '' }}">{{ $item['label'] }}</label>
                    </div>
                    @endforeach
                </form>

                @if($isSupervisor && $task->assignee)
                <p class="text-muted fs-12 mt-2 mb-0">Assigned to: {{ $task->assignee->name }}</p>
                @endif

                <div class="mt-3 d-flex gap-2">
                    @if($task->status !== 'cleaned' && $task->status !== 'verified')
                    <form action="{{ route('hotel.housekeeping.cleaned', $task->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="feather-check me-1"></i> Mark Cleaned
                        </button>
                    </form>
                    @endif

                    @if($isSupervisor && $task->status === 'cleaned')
                    <form action="{{ route('hotel.housekeeping.verify', $task->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="feather-shield me-1"></i> Verify &amp; Make Available
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
