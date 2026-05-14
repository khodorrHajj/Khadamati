@extends('layouts.citizen')

@section('title', 'Available Services')
@section('page-title', $category->name . ' Services')

@section('content')
    <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-1">{{ $category->name }}</h2>
                <p class="text-muted mb-0">{{ $office->name }}</p>
            </div>
            <a href="{{ route('citizen.offices.show', $office) }}" class="btn btn-secondary btn-sm">Back to Categories</a>
        </div>
    </div>

    <div class="row">
        @forelse ($services as $service)
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h5">{{ $service->name }}</h3>
                        <p>{{ $service->description ?: 'No service description available.' }}</p>
                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span>${{ number_format((float) $service->price, 2) }}</span>
                            <span>{{ $service->duration_days }} day{{ $service->duration_days === 1 ? '' : 's' }}</span>
                        </div>
                        <a href="{{ route('citizen.services.request.create', $service) }}" class="btn btn-primary btn-sm">Start Request</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No active services are available in this category.</div>
            </div>
        @endforelse
    </div>
@endsection
