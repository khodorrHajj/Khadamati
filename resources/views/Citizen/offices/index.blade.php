@extends('layouts.citizen')

@section('title', 'Browse Offices')
@section('page-title', 'Browse Government Offices')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Find An Office</h3>
        </div>
        <form method="GET" action="{{ route('citizen.offices.index') }}" id="office-filter-form">
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
                    <div class="col-12">
                        <button type="button" id="use-location-btn" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-map-marker-alt mr-1"></i> Use My Location
                        </button>
                        <small class="text-muted ml-2">Auto-fills your coordinates and searches nearby offices.</small>
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

    {{-- Map / List toggle --}}
    <ul class="nav nav-tabs mb-3" id="view-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="list-tab" data-toggle="tab" href="#list-view" role="tab">
                <i class="fas fa-list mr-1"></i> List View
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="map-tab" data-toggle="tab" href="#map-view" role="tab">
                <i class="fas fa-map mr-1"></i> Map View
            </a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- List View --}}
        <div class="tab-pane fade show active" id="list-view" role="tabpanel">
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
        </div>

        {{-- Map View --}}
        <div class="tab-pane fade" id="map-view" role="tabpanel">
            @if ($mapOffices->isNotEmpty())
                <div class="card">
                    <div class="card-body p-0">
                        <div id="offices-map" style="height: 520px; width: 100%;"></div>
                    </div>
                </div>
            @else
                <div class="alert alert-info">No offices with map coordinates are available to display.</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Geolocation
    document.getElementById('use-location-btn').addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser. Please enter coordinates manually.');
            return;
        }
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Getting location...';
        navigator.geolocation.getCurrentPosition(
            function (position) {
                document.getElementById('office_latitude').value = position.coords.latitude.toFixed(6);
                document.getElementById('office_longitude').value = position.coords.longitude.toFixed(6);
                document.getElementById('office-filter-form').submit();
            },
            function () {
                alert('Unable to retrieve your location. Please enter coordinates manually.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-map-marker-alt mr-1"></i> Use My Location';
            }
        );
    });

    // Map View — lazy init with Leaflet when tab is first opened
    @if ($mapOffices->isNotEmpty())
    var mapInitialized = false;
    var officesData = {!! $mapOfficesJson !!};

    function initOfficesMap() {
        if (mapInitialized) return;
        mapInitialized = true;

        var map = L.map('offices-map').setView([33.8547, 35.8623], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        officesData.forEach(function (office) {
            var popup = '<div style="max-width:220px;">'
                + '<strong>' + office.name + '</strong>'
                + (office.city ? '<br><small class="text-muted">' + office.city + '</small>' : '')
                + '<br><a href="' + office.url + '" style="font-size:12px;">View Office</a>';
            if (office.maps_url) {
                popup += ' &nbsp;<a href="' + office.maps_url + '" target="_blank" style="font-size:12px;">Open Maps</a>';
            }
            popup += '</div>';
            L.marker([office.lat, office.lng]).addTo(map).bindPopup(popup);
        });
    }

    document.getElementById('map-tab').addEventListener('shown.bs.tab', initOfficesMap);
    $('a[href="#map-view"]').on('shown.bs.tab', initOfficesMap);
    @endif
</script>
@if ($mapOffices->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
@endif
@endpush

@if ($mapOffices->isNotEmpty())
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css"/>
@endpush
@endif
