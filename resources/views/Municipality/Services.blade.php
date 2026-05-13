@extends('layouts.municipality')

@section('title', 'Services')
@section('page-title', 'Manage Services')

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
                        <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="form-control @error('price') is-invalid @enderror">
                        @error('price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label>Duration in Days</label>
                        <input type="number" name="duration_days" value="{{ old('duration_days') }}" class="form-control @error('duration_days') is-invalid @enderror">
                        @error('duration_days')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Required Documents</label>
                    <textarea name="required_documents" class="form-control @error('required_documents') is-invalid @enderror" rows="3">{{ old('required_documents') }}</textarea>
                    @error('required_documents')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create Service</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Existing Services for {{ $office->name }}</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Required Documents</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td>{{ $service->id }}</td>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->serviceCategory ? $service->serviceCategory->name : '-' }}</td>
                            <td>{{ number_format($service->price, 2) }}</td>
                            <td>{{ $service->duration_days }} days</td>
                            <td>{{ $service->required_documents ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No services found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
