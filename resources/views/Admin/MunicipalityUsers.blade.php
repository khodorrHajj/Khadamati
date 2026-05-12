@extends('layouts.admin')

@section('title', 'Municipality Users')
@section('page-title', 'Manage Municipality Users')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Municipality User</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.municipality.users.store') }}">
                @csrf

                <div class="form-group">
                    <label>Government Office</label>
                    <select name="government_office_id" class="custom-select @error('government_office_id') is-invalid @enderror">
                        <option value="">Select Government Office</option>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" {{ old('government_office_id') == $office->id ? 'selected' : '' }}>
                                {{ $office->municipality ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('government_office_id')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                    @error('email')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                    @error('phone')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Job Title / Position</label>
                    <input type="text" name="job_title" value="{{ old('job_title') }}" class="form-control @error('job_title') is-invalid @enderror">
                    @error('job_title')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                    @error('password')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Existing Municipality Users</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.municipality.users') }}">
                <div class="input-group">
                    <input type="text" name="search" value="{{ old('search', $search) }}" class="form-control" placeholder="Search by name, email, office, or municipality">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if ($search)
                            <a href="{{ route('admin.municipality.users') }}" class="btn btn-secondary">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Office</th>
                        <th>Municipality</th>
                        <th>Job Title / Position</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?: '-' }}</td>
                            <td>
                                @if ($user->governmentOffice)
                                    {{ $user->governmentOffice->name }}
                                @else
                                    No Office Assigned
                                @endif
                            </td>
                            <td>
                                @if ($user->governmentOffice && $user->governmentOffice->municipality)
                                    {{ $user->governmentOffice->municipality->name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $user->job_title ?: '-' }}</td>
                            <td>
                                @if ($user->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if (Route::has('admin.municipality.users.toggle-status'))
                                    <form method="POST" action="{{ route('admin.municipality.users.toggle-status', $user) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $user->status === 'active' ? 'btn-secondary' : 'btn-success' }}">
                                            {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No municipality users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
