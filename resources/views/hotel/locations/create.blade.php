@extends('layouts.hotel')
@section('title', 'Add Location')
@section('page_title', 'Add Location')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.locations.index') }}">Locations</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('hotel.locations.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-bold">Location name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Phone</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" required>
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Address</label>
                <input type="text" name="address" class="form-control">
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">City</label>
                    <input type="text" name="city" class="form-control">
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">State</label>
                    <input type="text" name="state" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Add Location</button>
        </form>
    </div>
</div>
@endsection
