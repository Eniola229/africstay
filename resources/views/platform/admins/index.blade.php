@extends('layouts.platform.app')
@section('title', 'Platform Admins')
@section('page_title', 'Platform Admins')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Add Platform Admin</h5></div>
            <div class="card-body">
                <form action="{{ route('platform.admins.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role</label>
                        <select name="role" class="form-select">
                            <option value="support">Support</option>
                            <option value="finance">Finance</option>
                            <option value="operations">Operations</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Temporary Password</label>
                        <input type="text" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Create Admin</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">All Platform Admins</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        @foreach($admins as $admin)
                        <tr>
                            <td>{{ $admin->name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>
                                <form action="{{ route('platform.admins.change-role', $admin->id) }}" method="POST">
                                    @csrf
                                    <select name="role" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                                        @foreach(['support','finance','operations','super_admin'] as $r)
                                        <option value="{{ $r }}" {{ $admin->role === $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td><span class="badge {{ $admin->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $admin->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <form action="{{ route('platform.admins.toggle-active', $admin->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-dark">{{ $admin->is_active ? 'Deactivate' : 'Activate' }}</button>
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
