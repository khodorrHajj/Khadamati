@extends('layouts.admin')

@section('title', 'Manage Government Offices')
@section('page-title', 'Manage Government Offices')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Government Offices</h3>
            <a href="{{ route('admin.offices.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Government Office
            </a>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('admin.offices.index') }}" class="mb-3">
                <div class="input-group">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search by office name, municipality, service type, phone, or email..."
                        value="{{ request('search') }}"
                    >
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        @if(request('search'))
                            <a href="{{ route('admin.offices.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Office Name</th>
                            <th>Municipality</th>
                            <th>Service Type</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($offices as $office)
                            <tr>
                                <td>{{ $office->id }}</td>
                                <td>{{ $office->name }}</td>
                                <td>{{ $office->municipality->name ?? 'No Municipality' }}</td>
                                <td>{{ $office->service_type ?? '-' }}</td>
                                <td>{{ $office->phone ?? '-' }}</td>
                                <td>{{ $office->email ?? '-' }}</td>
                                <td>
                                    @if($office->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.offices.show', $office) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.offices.edit', $office) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.offices.destroy', $office) }}"
                                        style="display:inline-block;"
                                        onsubmit="return confirm('Are you sure you want to delete this government office?')"
                                    >
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
                                <td colspan="8" class="text-center text-muted">No government offices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($offices->hasPages())
            <div class="card-footer">
                {{ $offices->links() }}
            </div>
        @endif
    </div>
@endsection
