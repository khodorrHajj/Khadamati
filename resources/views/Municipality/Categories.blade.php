@extends('layouts.municipality')

@section('title', 'Service Categories')
@section('page-title', 'Manage Service Categories')

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
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->description ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
