@extends('layouts.admin')

@section('title', 'Citizens')
@section('page-title', 'Manage Citizens')

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
            <h3 class="card-title">Citizen Accounts</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.citizens.index') }}">
                <div class="input-group">
                    <input type="text" name="search" value="{{ old('search', $search) }}" class="form-control" placeholder="Search by name, email, or phone">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if ($search)
                            <a href="{{ route('admin.citizens.index') }}" class="btn btn-secondary">Clear</a>
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
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($citizens as $citizen)
                        <tr>
                            <td>{{ $citizen->id }}</td>
                            <td>{{ $citizen->name }}</td>
                            <td>{{ $citizen->email }}</td>
                            <td>{{ $citizen->phone ?: '-' }}</td>
                            <td>
                                @if ($citizen->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $citizen->created_at ? $citizen->created_at->format('Y-m-d') : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.citizens.show', $citizen) }}" class="btn btn-info btn-sm">View</a>

                                @if ($citizen->status === 'active')
                                    <form method="POST" action="{{ route('admin.citizens.deactivate', $citizen) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary btn-sm">Deactivate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.citizens.activate', $citizen) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No citizens found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($citizens->hasPages())
            <div class="card-footer">
                {{ $citizens->links() }}
            </div>
        @endif
    </div>
@endsection
