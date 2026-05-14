@extends('layouts.municipality')

@section('title', 'Edit Service Category')
@section('page-title', 'Edit Service Category')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Edit Category for {{ $office->name }}</h3>
            <a href="{{ route('municipality.categories') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('municipality.categories.update', $category) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Category
                </button>
                <a href="{{ route('municipality.categories') }}" class="btn btn-secondary ml-2">
                    Cancel
                </a>
            </form>
        </div>
    </div>
@endsection
