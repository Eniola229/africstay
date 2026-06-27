@extends('layouts.hotel')
@section('title', 'Occupied Rooms')
@section('page_title', 'Currently Occupied Rooms')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Occupied Rooms</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th></tr></thead>
            <tbody>
                @foreach($rooms as $room)
                @php $booking = $room->bookings->first(); @endphp
                <tr>
                    <td>Room {{ $room->room_number }}</td>
                    <td>{{ $booking?->guest->name ?? '—' }}</td>
                    <td>{{ $booking?->check_in->format('d M Y') }}</td>
                    <td>{{ $booking?->check_out->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($rooms->isEmpty())<p class="text-muted text-center py-5 mb-0">No rooms currently occupied.</p>@endif
    </div>
</div>
@endsection
