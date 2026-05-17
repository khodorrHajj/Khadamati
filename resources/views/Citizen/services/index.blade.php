@extends('layouts.citizen')

@section('title', 'Browse Services')
@section('page-title', 'Browse Services')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Find a Service</h3>
        </div>
        <form method="GET" action="{{ route('citizen.services.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Service name">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="municipality">Municipality</label>
                            <select name="municipality" id="municipality" class="form-control">
                                <option value="">All municipalities</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" @selected((string) ($filters['municipality'] ?? '') === (string) $municipality->id)>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="office">Office</label>
                            <select name="office" id="office" class="form-control">
                                <option value="">All offices</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" @selected((string) ($filters['office'] ?? '') === (string) $office->id)>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" id="category" class="form-control">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('citizen.services.index') }}" class="btn btn-secondary">Reset</a>
                <div>
                    <button type="button" id="sort-nearest-btn" class="btn btn-outline-info mr-2">
                        <i class="fas fa-crosshairs mr-1"></i> Nearest to Me
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="row" id="services-grid">
        @forelse ($services as $service)
            <div class="col-xl-4 col-md-6 service-card-col"
                 data-lat="{{ $service->governmentOffice?->latitude }}"
                 data-lng="{{ $service->governmentOffice?->longitude }}"
                 data-dist="">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">{{ $service->name }}</h3>
                        <span class="badge badge-info distance-badge d-none" style="font-size:11px;"></span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <dl class="row mb-3">
                            <dt class="col-sm-5">Category</dt>
                            <dd class="col-sm-7">{{ $service->serviceCategory?->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Office</dt>
                            <dd class="col-sm-7">{{ $service->governmentOffice?->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Municipality</dt>
                            <dd class="col-sm-7">{{ $service->governmentOffice?->municipality?->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Price</dt>
                            <dd class="col-sm-7">{{ $service->formattedPrice() }}</dd>

                            <dt class="col-sm-5">Duration</dt>
                            <dd class="col-sm-7">{{ $service->durationLabel() }}</dd>

                            <dt class="col-sm-5">Location</dt>
                            <dd class="col-sm-7">{{ $service->governmentOffice?->city ?: ($service->governmentOffice?->formatted_address ?: 'See office page') }}</dd>
                        </dl>

                        <div class="mb-3">
                            <strong>Required Documents</strong>
                            @php($documents = $service->requiredDocumentList())
                            @if (count($documents))
                                <ul class="pl-3 mb-0">
                                    @foreach ($documents as $document)
                                        <li>{{ $document }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted mb-0">No required documents listed.</p>
                            @endif
                        </div>

                        <div class="mt-auto">
                            <a href="{{ route('citizen.services.request.create', $service) }}" class="btn btn-primary btn-block">
                                Start Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No active services match your filters.</div>
            </div>
        @endforelse
    </div>

    @if ($services->hasPages())
        <div class="mt-3">
            {{ $services->links() }}
        </div>
    @endif

@push('scripts')
<script>
document.getElementById('sort-nearest-btn').addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Locating...';

    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-crosshairs mr-1"></i> Nearest to Me';
        return;
    }

    navigator.geolocation.getCurrentPosition(function(pos) {
        var userLat = pos.coords.latitude;
        var userLng = pos.coords.longitude;

        function haversine(lat1, lng1, lat2, lng2) {
            var R = 6371;
            var dLat = (lat2 - lat1) * Math.PI / 180;
            var dLng = (lng2 - lng1) * Math.PI / 180;
            var a = Math.sin(dLat/2) * Math.sin(dLat/2)
                  + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180)
                  * Math.sin(dLng/2) * Math.sin(dLng/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        var cols = Array.from(document.querySelectorAll('.service-card-col'));
        cols.forEach(function(col) {
            var lat = parseFloat(col.dataset.lat);
            var lng = parseFloat(col.dataset.lng);
            if (lat && lng) {
                var d = haversine(userLat, userLng, lat, lng);
                col.dataset.dist = d;
                var badge = col.querySelector('.distance-badge');
                badge.textContent = d.toFixed(1) + ' km';
                badge.classList.remove('d-none');
            } else {
                col.dataset.dist = 99999;
            }
        });

        cols.sort(function(a, b) {
            return parseFloat(a.dataset.dist) - parseFloat(b.dataset.dist);
        });

        var grid = document.getElementById('services-grid');
        cols.forEach(function(col) { grid.appendChild(col); });

        btn.innerHTML = '<i class="fas fa-check mr-1"></i> Sorted by Distance';
        btn.disabled = false;
    }, function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-crosshairs mr-1"></i> Nearest to Me';
        alert('Location access denied. Please allow location in your browser.');
    });
});
</script>
@endpush
@endsection
