@extends('layouts.municipality')

@section('title', 'Service Categories')
@section('page-title', 'Manage Service Categories')

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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Category</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('municipality.categories.store') }}">
                @csrf

                <div class="form-group">
                    <label>Name</label>
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

                <button type="submit" class="btn btn-primary">Create Category</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Existing Categories for {{ $office->name }}</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('municipality.categories') }}">
                <div class="input-group">
                    <input type="text" name="search" value="{{ old('search', $search) }}" class="form-control" placeholder="Search by name or description">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if ($search)
                            <a href="{{ route('municipality.categories') }}" class="btn btn-secondary">Clear</a>
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
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->description ?: '-' }}</td>
                            <td>
                                <a href="{{ route('municipality.categories.edit', $category) }}" class="btn btn-warning btn-sm">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('municipality.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="card-footer">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
@endsection
