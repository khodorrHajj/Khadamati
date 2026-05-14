@extends('layouts.citizen')

@section('title', 'Service Categories')
@section('page-title', $office->name . ' Categories')

@section('content')
    <div class="card">
        <div class="card-body">
            <h2 class="h4">{{ $office->name }}</h2>
            <p class="text-muted mb-0">{{ $office->municipality?->name ?? 'Municipality not assigned' }}</p>
        </div>
    </div>

    <div class="row">
        @forelse ($categories as $category)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h5">{{ $category->name }}</h3>
                        <p>{{ $category->description ?: 'No category description available.' }}</p>
                        <p class="text-muted">{{ $category->active_services_count }} active service{{ $category->active_services_count === 1 ? '' : 's' }}</p>
                        <a href="{{ route('citizen.categories.show', [$office, $category]) }}" class="btn btn-outline-primary btn-sm">Browse Services</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No active service categories are available for this office.</div>
            </div>
        @endforelse
    </div>
@endsection
