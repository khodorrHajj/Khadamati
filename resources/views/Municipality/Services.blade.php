@extends('layouts.municipality')

@section('title', 'Services')
@section('page-title', 'Manage Services')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Service</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('municipality.services.store') }}">
                        @csrf

                        <div class="form-group">
                            <label>Category</label>
                            <select name="service_category_id" class="custom-select @error('service_category_id') is-invalid @enderror">
                                <option value="">Select Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_category_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Service Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Price</label>
                                <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" class="form-control @error('price') is-invalid @enderror">
                                @error('price')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label>Duration in Days</label>
                                <input type="number" min="1" name="duration_days" value="{{ old('duration_days') }}" class="form-control @error('duration_days') is-invalid @enderror">
                                @error('duration_days')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Required Documents</label>
                            <textarea name="required_documents" class="form-control @error('required_documents') is-invalid @enderror" rows="4" placeholder="One document per line">{{ old('required_documents') }}</textarea>
                            <small class="form-text text-muted">Keep using plain text. Enter one document per line.</small>
                            @error('required_documents')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Create Service</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Services for {{ $office->name }}</h3>
                </div>
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('municipality.services') }}">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group mb-md-0">
                                    <label class="sr-only">Search</label>
                                    <input
                                        type="text"
                                        name="search"
                                        value="{{ old('search', $search) }}"
                                        class="form-control"
                                        placeholder="Search by service name">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label class="sr-only">Category</label>
                                    <select name="category" class="custom-select">
                                        <option value="">All Categories</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" {{ (string) $selectedCategory === (string) $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label class="sr-only">Status</label>
                                    <select name="status" class="custom-select">
                                        <option value="">All Statuses</option>
                                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="btn-group d-flex">
                                    <button type="submit" class="btn btn-primary w-100">Search</button>
                                    @if ($search || $selectedCategory || $status)
                                        <a href="{{ route('municipality.services') }}" class="btn btn-secondary w-100">Clear</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Required Documents</th>
                                <th style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    <td>
                                        <strong>{{ $service->name }}</strong>
                                        @if ($service->description)
                                            <div class="text-muted small mt-1">{{ $service->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $service->serviceCategory ? $service->serviceCategory->name : '-' }}</td>
                                    <td>${{ number_format((float) $service->price, 2) }}</td>
                                    <td>{{ $service->duration_days }} day{{ $service->duration_days === 1 ? '' : 's' }}</td>
                                    <td>
                                        <span class="badge {{ $service->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $service->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php($documents = preg_split('/\r\n|\r|\n/', (string) $service->required_documents, -1, PREG_SPLIT_NO_EMPTY))
                                        @if (count($documents))
                                            @foreach ($documents as $document)
                                                <div>{{ $document }}</div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('municipality.services.edit', $service) }}" class="btn btn-warning btn-sm mb-1">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('municipality.services.toggle-status', $service) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-secondary btn-sm mb-1">
                                                {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('municipality.services.destroy', $service) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm mb-1">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No services found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($services->hasPages())
                    <div class="card-footer">
                        {{ $services->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
