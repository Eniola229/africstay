@extends('layouts.hotel')
@section('title', 'Arrivals & Departures')
@section('page_title', 'Daily Arrivals & Departures')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active">Arrivals &amp; Departures</li>
@endsection

@section('content')
@include('hotel.reports.partials.date-filter')

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Arrivals ({{ $arrivals->count() }})</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($arrivals as $b)
                        <tr>
                            <td>{{ $b->guest->name }}</td>
                            <td>Room {{ $b->room->room_number }}</td>
                            <td>{{ $b->check_in->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($arrivals->isEmpty())<p class="text-muted text-center py-3 mb-0">No arrivals in this range.</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Departures ({{ $departures->count() }})</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($departures as $b)
                        <tr>
                            <td>{{ $b->guest->name }}</td>
                            <td>Room {{ $b->room->room_number }}</td>
                            <td>{{ $b->check_out->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($departures->isEmpty())<p class="text-muted text-center py-3 mb-0">No departures in this range.</p>@endif
            </div>
        </div>
    </div>
</div>
@endsection
