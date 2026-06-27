@extends('layouts.hotel')
@section('title', 'Room Service Menu')
@section('page_title', 'Room Service Menu')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('hotel.room-service.orders') }}">Room Service</a></li>
    <li class="breadcrumb-item active">Menu</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Add Item</h5></div>
            <div class="card-body">
                <form action="{{ route('hotel.room-service.items.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" class="form-select">
                            <option value="food">Food</option>
                            <option value="drink">Drink</option>
                            <option value="laundry">Laundry</option>
                            <option value="misc">Miscellaneous</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Price (₦)</label>
                        <input type="number" name="price" min="0" class="form-control @error('price') is-invalid @enderror" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add to Menu</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Menu Items</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fs-11 text-uppercase text-muted fw-semibold">Item</th>
                            <th class="fs-11 text-uppercase text-muted fw-semibold">Category</th>
                            <th class="fs-11 text-uppercase text-muted fw-semibold">Price</th>
                            <th class="fs-11 text-uppercase text-muted fw-semibold">Status</th>
                            <th class="fs-11 text-uppercase text-muted fw-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td class="text-capitalize">{{ $item->category }}</td>
                            <td class="fw-bold">₦{{ number_format($item->priceNaira(), 2) }}</td>
                            <td><span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <form action="{{ route('hotel.room-service.items.toggle', $item->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $item->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
