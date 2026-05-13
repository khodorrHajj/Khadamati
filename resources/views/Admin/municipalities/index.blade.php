@extends('layouts.admin')

@section('title', 'Manage Municipalities')
@section('page-title', 'Manage Municipalities')

@section('content')

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Municipalities List</h3>
            <a href="{{ route('admin.municipalities.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Municipality
            </a>
        </div>

        <div class="card-body">

            {{-- Search Form --}}
            <form method="GET" action="{{ route('admin.municipalities.index') }}" class="mb-3">
                <div class="input-group">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search by name, city, email or phone..."
                        value="{{ request('search') }}"
                    >
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        @if(request('search'))
                            <a href="{{ route('admin.municipalities.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($municipalities as $municipality)
                            <tr>
                                <td>{{ $municipality->id }}</td>
                                <td>{{ $municipality->name }}</td>
                                <td>{{ $municipality->city ?? '—' }}</td>
                                <td>{{ $municipality->phone ?? '—' }}</td>
                                <td>{{ $municipality->email ?? '—' }}</td>
                                <td>
                                    @if($municipality->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.municipalities.show', $municipality) }}"
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.municipalities.edit', $municipality) }}"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.municipalities.destroy', $municipality) }}"
                                          method="POST"
                                          style="display:inline-block;"
                                          onsubmit="return confirm('Are you sure you want to delete this municipality?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    No municipalities found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        {{-- Pagination --}}
        @if($municipalities->hasPages())
            <div class="card-footer">
                {{ $municipalities->links() }}
            </div>
        @endif

    </div>

@endsection