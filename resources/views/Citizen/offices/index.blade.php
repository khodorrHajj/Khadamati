@extends('layouts.citizen')

@section('title', 'Browse Offices')
@section('page-title', 'Browse Government Offices')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Find An Office</h3>
        </div>
        <form method="GET" action="{{ route('citizen.offices.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="office_search">Search</label>
                            <input type="text" name="search" id="office_search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Office, municipality, service, or city">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="office_municipality">Municipality</label>
                            <select name="municipality" id="office_municipality" class="form-control">
                                <option value="">All municipalities</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" @selected((string) ($filters['municipality'] ?? '') === (string) $municipality->id)>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="office_category">Category</label>
                            <select name="category" id="office_category" class="form-control">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="office_latitude">Your Latitude</label>
                            <input type="text" name="latitude" id="office_latitude" value="{{ $filters['latitude'] ?? '' }}" class="form-control" placeholder="33.8938">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="office_longitude">Your Longitude</label>
                            <input type="text" name="longitude" id="office_longitude" value="{{ $filters['longitude'] ?? '' }}" class="form-control" placeholder="35.5018">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="radius_km">Radius (km)</label>
                            <input type="number" min="1" max="500" step="1" name="radius_km" id="radius_km" value="{{ $filters['radius_km'] ?? '' }}" class="form-control" placeholder="25">
                            <small class="form-text text-muted">Add your coordinates to sort by nearest offices and optionally limit the radius.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('citizen.offices.index') }}" class="btn btn-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search-location mr-1"></i> Find Offices
                </button>
            </div>
        </form>
    </div>

    @if ($hasLocationFilter)
        <div class="alert alert-info">
            Offices are sorted by distance from the coordinates you entered.
        </div>
    @endif

    <div class="row">
        @forelse ($offices as $office)
            <div class="col-md-6 col-lg-4">
                <div class="card card-outline card-primary h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5">{{ $office->name }}</h3>
                        <p class="text-muted mb-2">{{ $office->municipality?->name ?? 'Municipality not assigned' }}</p>
                        <p class="mb-2">{{ $office->service_type ?: 'Public services available through this office.' }}</p>
                        <p class="text-muted mb-2">{{ $office->city ?: 'City not listed' }}</p>
                        <div class="mb-3">
                            <span class="badge badge-light border">{{ $office->active_services_count }} active service{{ $office->active_services_count === 1 ? '' : 's' }}</span>
                            @if (isset($office->distance_km) && $office->distance_km !== null)
                                <span class="badge badge-info ml-1">{{ number_format((float) $office->distance_km, 1) }} km away</span>
                            @endif
                        </div>
                        <div class="mt-auto">
                            <a href="{{ route('citizen.offices.show', $office) }}" class="btn btn-primary btn-sm">View Office</a>
                            @if ($office->google_maps_url)
                                <a href="{{ $office->google_maps_url }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Open Map</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No active offices with services are available right now.</div>
            </div>
        @endforelse
    </div>

    @if ($offices->hasPages())
        <div class="mt-3">
            {{ $offices->links() }}
        </div>
    @endif
@endsection
