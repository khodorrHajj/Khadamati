@extends('layouts.citizen')

@section('title', 'Start Request')
@section('page-title', 'Start Request')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Office location map + nearest offices --}}
    @if ($officeMapData || ($nearbyOffices ?? collect())->isNotEmpty())
    <div class="row mb-3">
        @if ($officeMapData)
        <div class="col-lg-{{ $nearbyOffices->isNotEmpty() ? '8' : '12' }}">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-map-marker-alt mr-1 text-danger"></i> Office Location</h3>
                    @if ($officeMapData['maps_url'])
                        <a href="{{ $officeMapData['maps_url'] }}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-directions mr-1"></i> Get Directions
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @php
                        $bbox = ($officeMapData['lng']-0.008).','.($officeMapData['lat']-0.005).','.($officeMapData['lng']+0.008).','.($officeMapData['lat']+0.005);
                    @endphp
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ $bbox }}&layer=mapnik&marker={{ $officeMapData['lat'] }},{{ $officeMapData['lng'] }}"
                        style="height:260px; width:100%; border:0;"
                        loading="lazy"
                        title="Office Location Map">
                    </iframe>
                </div>
                <div class="card-footer text-muted small">
                    {{ $officeMapData['name'] }}@if($officeMapData['city']), {{ $officeMapData['city'] }}@endif
                    @if($officeMapData['address']) &mdash; {{ $officeMapData['address'] }}@endif
                    @if($officeMapData['phone']) &mdash; <i class="fas fa-phone mr-1"></i>{{ $officeMapData['phone'] }}@endif
                </div>
            </div>
        </div>
        @endif

        @if ($nearbyOffices->isNotEmpty())
        <div class="col-lg-{{ $officeMapData ? '4' : '12' }}">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="fas fa-search-location mr-1 text-primary"></i> Other Offices with This Service</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" id="nearby-list">
                        @foreach ($nearbyOffices as $nearOffice)
                        <li class="list-group-item px-3 py-2"
                            data-lat="{{ $nearOffice->latitude }}"
                            data-lng="{{ $nearOffice->longitude }}"
                            data-name="{{ $nearOffice->name }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $nearOffice->name }}</strong>
                                    @if ($nearOffice->city)
                                        <small class="text-muted d-block">{{ $nearOffice->city }}</small>
                                    @endif
                                    <small class="text-info distance-label"></small>
                                </div>
                                <div class="d-flex flex-column align-items-end ml-2">
                                    @if ($nearOffice->google_maps_url)
                                        <a href="{{ $nearOffice->google_maps_url }}" target="_blank" class="btn btn-xs btn-outline-success mb-1" style="font-size:11px;padding:2px 7px;">
                                            <i class="fas fa-directions"></i> Maps
                                        </a>
                                    @endif
                                    <a href="{{ route('citizen.offices.show', $nearOffice) }}" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 7px;">
                                        View
                                    </a>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer p-2">
                    <button id="use-location-btn" class="btn btn-sm btn-outline-primary btn-block">
                        <i class="fas fa-crosshairs mr-1"></i> Sort by Distance from Me
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Service Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">Service</th>
                                <td>{{ $service->name }}</td>
                            </tr>
                            <tr>
                                <th>Office</th>
                                <td>{{ $service->governmentOffice?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>{{ $service->serviceCategory?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Price</th>
                                <td>{{ $service->formattedPrice() }}</td>
                            </tr>
                            <tr>
                                <th>Duration</th>
                                <td>{{ $service->durationLabel() }}</td>
                            </tr>
                            <tr>
                                <th>Required Documents</th>
                                <td>
                                    @php($documents = $service->requiredDocumentList())
                                    @if (count($documents))
                                        <ul class="pl-3 mb-0">
                                            @foreach ($documents as $document)
                                                <li>{{ $document }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">No required documents listed.</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Request Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('citizen.services.request.store', $service) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="notes">Notes / Message</label>
                            <textarea name="notes" id="notes" rows="5" class="form-control @error('notes') is-invalid @enderror" placeholder="Add any details the office should know.">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="documents">Upload Documents</label>
                            <input type="file" name="documents[]" id="documents" multiple class="form-control-file @error('documents.*') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*">
                            <small class="form-text text-muted">Upload one or more PDF or image files. Maximum size is 5 MB per file.</small>
                            @error('documents.*')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($officeMapData)
    @push('scripts')
    <script>
        (function() {
            var nearbyOffices = {!! $nearbyOfficesJson !!};

            // Sort by distance button
            var locBtn = document.getElementById('use-location-btn');
            if (locBtn) {
                locBtn.addEventListener('click', function() {
                    var btn = this;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Locating...';

                    navigator.geolocation.getCurrentPosition(function(pos) {
                        var userLat = pos.coords.latitude;
                        var userLng = pos.coords.longitude;

                        function haversine(lat1, lng1, lat2, lng2) {
                            var R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLng = (lng2-lng1)*Math.PI/180;
                            var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
                            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                        }

                        var items = Array.from(document.querySelectorAll('#nearby-list .list-group-item'));
                        items.forEach(function(el) {
                            var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
                            var d = haversine(userLat, userLng, lat, lng);
                            el.dataset.dist = d;
                            el.querySelector('.distance-label').textContent = d.toFixed(1) + ' km away';
                        });

                        items.sort(function(a, b) { return parseFloat(a.dataset.dist) - parseFloat(b.dataset.dist); });
                        var list = document.getElementById('nearby-list');
                        items.forEach(function(el) { list.appendChild(el); });

                        btn.innerHTML = '<i class="fas fa-check mr-1"></i> Sorted by Distance';
                    }, function() {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-crosshairs mr-1"></i> Sort by Distance from Me';
                        alert('Location access denied.');
                    });
                });
            }
        })();
    </script>
    @endpush
    @endif
@endsection
