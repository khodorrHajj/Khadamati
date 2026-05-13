@php
    $mapsKey = config('services.google.maps_key');
    $mapModel = $mapModel ?? null;
    $mapLatitude = old('latitude', $mapModel->latitude ?? null);
    $mapLongitude = old('longitude', $mapModel->longitude ?? null);
    $mapPlaceId = old('place_id', $mapModel->place_id ?? null);
    $mapAddress = old('formatted_address', $mapModel->formatted_address ?? null);
@endphp

<h5 class="mb-3 mt-4 border-bottom pb-2">Map Location</h5>

@if(!$mapsKey)
    <div class="alert alert-warning">
        Google Maps API key is not configured. You can still paste a Google Maps URL manually.
    </div>
@endif

<div class="form-group">
    <label>Search location on Google Maps</label>
    <input
        type="text"
        id="office_map_search"
        class="form-control"
        placeholder="Search location on Google Maps"
        {{ $mapsKey ? '' : 'disabled' }}
    >
</div>

<div id="government_office_map" class="border rounded mb-3" style="height: 350px; width: 100%;"></div>

<div class="alert alert-info mb-3" id="office_selected_address_display">
    @if($mapAddress)
        Selected address: {{ $mapAddress }}
    @else
        No map location selected yet.
    @endif
</div>

<input type="hidden" name="latitude" id="office_latitude" value="{{ $mapLatitude }}">
<input type="hidden" name="longitude" id="office_longitude" value="{{ $mapLongitude }}">
<input type="hidden" name="place_id" id="office_place_id" value="{{ $mapPlaceId }}">
<input type="hidden" name="formatted_address" id="office_formatted_address" value="{{ $mapAddress }}">

@error('latitude')
    <div class="text-danger mb-2">{{ $message }}</div>
@enderror
@error('longitude')
    <div class="text-danger mb-2">{{ $message }}</div>
@enderror
@error('place_id')
    <div class="text-danger mb-2">{{ $message }}</div>
@enderror
@error('formatted_address')
    <div class="text-danger mb-2">{{ $message }}</div>
@enderror

@if($mapsKey)
    @push('scripts')
        <script>
            function initGovernmentOfficeMap() {
                const defaultCenter = { lat: 33.8547, lng: 35.8623 };
                const latitudeInput = document.getElementById('office_latitude');
                const longitudeInput = document.getElementById('office_longitude');
                const savedLat = parseFloat(latitudeInput.value);
                const savedLng = parseFloat(longitudeInput.value);
                const hasSavedLocation = !Number.isNaN(savedLat) && !Number.isNaN(savedLng);
                const center = hasSavedLocation ? { lat: savedLat, lng: savedLng } : defaultCenter;

                const map = new google.maps.Map(document.getElementById('government_office_map'), {
                    center: center,
                    zoom: hasSavedLocation ? 15 : 8,
                    clickableIcons: false,
                });

                const marker = new google.maps.Marker({
                    map: map,
                    position: hasSavedLocation ? center : null,
                });
                const geocoder = new google.maps.Geocoder();
                const mapsUrlInput = document.querySelector('input[name="google_maps_url"]');
                const placeIdInput = document.getElementById('office_place_id');
                const addressInput = document.getElementById('office_formatted_address');
                const addressDisplay = document.getElementById('office_selected_address_display');

                function googleMapsUrl(latitude, longitude) {
                    return 'https://www.google.com/maps?q=' + latitude + ',' + longitude;
                }

                function updateSelectedLocation(latitude, longitude, placeId, address, mapsUrl) {
                    const displayText = address || (latitude + ', ' + longitude);

                    latitudeInput.value = latitude;
                    longitudeInput.value = longitude;
                    placeIdInput.value = placeId || '';
                    addressInput.value = address || displayText;
                    addressDisplay.textContent = 'Selected location: ' + displayText;

                    if (mapsUrlInput) {
                        mapsUrlInput.value = mapsUrl || googleMapsUrl(latitude, longitude);
                    }
                }

                function moveMarker(latitude, longitude) {
                    const position = { lat: latitude, lng: longitude };

                    marker.setPosition(position);
                    map.setCenter(position);
                    map.setZoom(16);
                }

                const searchInput = document.getElementById('office_map_search');
                const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                    fields: ['formatted_address', 'geometry', 'name', 'place_id', 'url'],
                });

                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();

                    if (!place.geometry || !place.geometry.location) {
                        return;
                    }

                    const location = place.geometry.location;
                    const latitude = location.lat();
                    const longitude = location.lng();
                    const address = place.formatted_address || place.name || '';
                    const mapsUrl = place.url || googleMapsUrl(latitude, longitude);

                    moveMarker(latitude, longitude);
                    updateSelectedLocation(latitude, longitude, place.place_id || '', address, mapsUrl);
                });

                map.addListener('click', function(event) {
                    const latitude = event.latLng.lat();
                    const longitude = event.latLng.lng();

                    moveMarker(latitude, longitude);
                    updateSelectedLocation(latitude, longitude, '', '', googleMapsUrl(latitude, longitude));

                    geocoder.geocode({ location: event.latLng }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            updateSelectedLocation(
                                latitude,
                                longitude,
                                results[0].place_id || '',
                                results[0].formatted_address || '',
                                googleMapsUrl(latitude, longitude)
                            );
                        }
                    });
                });
            }
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initGovernmentOfficeMap"></script>
    @endpush
@endif
