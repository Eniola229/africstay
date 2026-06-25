@extends('layouts.hotel')
@section('title', 'Rooms')
@section('page_title', 'Rooms')
@section('breadcrumb')
    <li class="breadcrumb-item active">Rooms</li>
@endsection
@section('page_actions')
    <a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary btn-sm">
        <i class="feather-plus me-1"></i> Add Room
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        @if($rooms->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Room</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Type</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Price/Night</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Media</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                        <th class="fs-11 text-uppercase text-muted fw-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rooms as $room)
                    @php
                        $statusBadge = match($room->status) {
                            'available' => 'bg-success',
                            'occupied' => 'bg-danger',
                            'dirty' => 'bg-warning text-dark',
                            'maintenance' => 'bg-info text-white',
                            default => 'bg-secondary',
                        };
                        $primaryImage = $room->media->firstWhere('type', 'image');
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($primaryImage)
                                <img src="{{ $primaryImage->url }}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                                @else
                                <div style="width:40px;height:40px;border-radius:6px;background:#f1f3f5;display:flex;align-items:center;justify-content:center;">
                                    <i class="feather-home text-muted" style="font-size:14px;"></i>
                                </div>
                                @endif
                                <span class="fw-semibold">Room {{ $room->room_number }}</span>
                            </div>
                        </td>
                        <td class="text-capitalize">{{ $room->type }}</td>
                        <td class="fw-bold">₦{{ number_format($room->pricePerNightNaira(), 2) }}</td>
                        <td class="text-muted fs-13">
                            <i class="feather-image" style="font-size:12px;"></i> {{ $room->media->where('type','image')->count() }}
                            &nbsp;<i class="feather-video" style="font-size:12px;"></i> {{ $room->media->where('type','video')->count() }}
                        </td>
                        <td><span class="badge {{ $statusBadge }} text-capitalize">{{ $room->status }}</span></td>
                        <td>
                            <a href="{{ route('hotel.rooms.edit', $room->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="feather-edit-2"></i> Edit
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $rooms->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="feather-home mb-2 d-block" style="font-size:40px;"></i>
            <p class="mb-3">No rooms yet.</p>
            <a href="{{ route('hotel.rooms.create') }}" class="btn btn-primary">Add your first room</a>
        </div>
        @endif
    </div>
</div>
@endsection
