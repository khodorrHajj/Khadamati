@extends('layouts.admin')

@section('title', 'Manage Municipalities')
@section('page-title', 'Manage Municipalities')

@section('content')

    {{-- Main Card --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-city mr-1"></i> Municipalities</h3>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createMunicipalityModal">
                <i class="fas fa-plus"></i> Add Municipality
            </button>
        </div>

        <div class="card-body p-0">
            <table id="municipalitiesTable" class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>City</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($municipalities as $municipality)
                        <tr>
                            <td><div class="font-weight-bold">{{ $municipality->name }}</div></td>
                            <td>{{ $municipality->city ?? '—' }}</td>
                            <td>
                                @if ($municipality->phone)
                                    <div><i class="fas fa-phone fa-xs mr-1 text-muted"></i> {{ $municipality->phone }}</div>
                                @endif
                                @if ($municipality->email)
                                    <div class="small"><i class="fas fa-envelope fa-xs mr-1 text-muted"></i> {{ $municipality->email }}</div>
                                @endif
                                @if (!$municipality->phone && !$municipality->email)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($municipality->status === 'active')
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('admin.municipalities.show', $municipality) }}" class="dropdown-item">
                                            <i class="fas fa-eye mr-2 text-info"></i> View Details
                                        </a>
                                        <a href="{{ route('admin.municipalities.edit', $municipality) }}" class="dropdown-item">
                                            <i class="fas fa-edit mr-2 text-warning"></i> Edit Municipality
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item text-danger btn-delete-municipality"
                                            data-url="{{ route('admin.municipalities.destroy', $municipality) }}"
                                            data-name="{{ $municipality->name }}">
                                            <i class="fas fa-trash mr-2"></i> Delete Municipality
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-city fa-2x mb-2 d-block text-muted"></i>
                                No municipalities found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- CREATE MUNICIPALITY MODAL - 3 Step Wizard --}}
    {{-- ============================================================ --}}
    <div class="modal fade" id="createMunicipalityModal" tabindex="-1" role="dialog" aria-labelledby="createMunicipalityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.municipalities.store') }}" id="createMunicipalityForm">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="createMunicipalityModalLabel">
                            <i class="fas fa-city mr-2"></i>Add Municipality
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    {{-- Single modal-body wrapping all steps --}}
                    <div class="modal-body" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                        {{-- Step Indicator --}}
                        <ul class="nav nav-pills nav-fill mb-3" id="municipalitySteps">
                            <li class="nav-item">
                                <a class="nav-link active" id="step1-tab" href="#">
                                    <span class="badge badge-primary mr-1">1</span> Basic Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" id="step2-tab" href="#">
                                    <span class="badge badge-secondary mr-1">2</span> Location
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" id="step3-tab" href="#">
                                    <span class="badge badge-secondary mr-1">3</span> Working Hours
                                </a>
                            </li>
                        </ul>

                    {{-- STEP 1: Basic Info --}}
                    <div class="step-content" id="step1Content">
                        <h6 class="text-muted mb-3"><i class="fas fa-info-circle mr-1"></i> Contact & Address Information</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Municipality name" required>
                                    @error('name')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="03XXXXXX or 70XXXXXX" required>
                                    @error('phone')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="office@municipality.gov" required>
                                    @error('email')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" value="{{ old('city') }}" class="form-control @error('city') is-invalid @enderror" placeholder="City name" required>
                                    @error('city')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Street <span class="text-danger">*</span></label>
                                    <input type="text" name="street" value="{{ old('street') }}" class="form-control @error('street') is-invalid @enderror" placeholder="Street name" required>
                                    @error('street')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Building <span class="text-danger">*</span></label>
                                    <input type="text" name="building" value="{{ old('building') }}" class="form-control @error('building') is-invalid @enderror" placeholder="Building number/name" required>
                                    @error('building')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="status" class="custom-select @error('status') is-invalid @enderror">
                                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional notes about this municipality...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 2: Location --}}
                    <div class="step-content" id="step2Content" style="display:none;">
                        <h6 class="text-muted mb-3"><i class="fas fa-map-marked-alt mr-1"></i> Map Location</h6>

                        @if(!$mapsKey)
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Google Maps API key is not configured. You can still paste a Google Maps URL manually.
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Google Maps URL</label>
                            <input type="url" name="google_maps_url" value="{{ old('google_maps_url') }}" class="form-control @error('google_maps_url') is-invalid @enderror" placeholder="https://maps.google.com/..." id="google_maps_url_input">
                            @error('google_maps_url')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        @if($mapsKey)
                            <div class="form-group">
                                <label>Search location on Google Maps</label>
                                <div class="input-group">
                                    <input type="text" id="map_search" class="form-control" placeholder="Search location on Google Maps">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary" id="map_search_btn">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="municipality_map" class="border rounded mb-3" style="height: 200px; width: 100%;"></div>

                            <div class="alert alert-info mb-3" id="selected_address_display">
                                No map location selected yet.
                            </div>
                        @endif

                        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                        <input type="hidden" name="place_id" id="place_id" value="{{ old('place_id') }}">
                        <input type="hidden" name="formatted_address" id="formatted_address" value="{{ old('formatted_address') }}">

                        @error('latitude')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        @error('longitude')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        @error('place_id')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        @error('formatted_address')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- STEP 3: Working Hours --}}
                    <div class="step-content" id="step3Content" style="display:none;">
                        <h6 class="text-muted mb-3"><i class="fas fa-clock mr-1"></i> Working Hours</h6>

                        {{-- Working Hours Errors --}}
                        @php
                            $whErrors = $errors->keys();
                            $whErrors = array_filter($whErrors, fn($k) => str_starts_with($k, 'working_hours'));
                        @endphp
                        @if(count($whErrors) > 0)
                            <div class="alert alert-danger">
                                @foreach($whErrors as $key)
                                    <div>{{ $errors->first($key) }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:140px">Day</th>
                                        <th style="width:80px" class="text-center">Open</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $weekdays = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                                    @endphp

                                    @foreach($days as $day)
                                        @php
                                            $isWeekday    = in_array($day, $weekdays);
                                            $defaultOpen  = $isWeekday ? '1' : '0';
                                            $oldIsOpen    = old("working_hours.{$day}.is_open", $defaultOpen);
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $day }}</strong></td>
                                            <td class="text-center">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="hidden" name="working_hours[{{ $day }}][is_open]" value="0">
                                                    <input type="checkbox" class="custom-control-input day-toggle" id="open_{{ $day }}" name="working_hours[{{ $day }}][is_open]" value="1" {{ $oldIsOpen == '1' ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="open_{{ $day }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="time" name="working_hours[{{ $day }}][start_time]" value="{{ old("working_hours.{$day}.start_time", '08:00') }}" class="form-control form-control-sm time-input-{{ $day }} @error("working_hours.{$day}.start_time") is-invalid @enderror" {{ $oldIsOpen == '1' ? '' : 'disabled' }}>
                                                @error("working_hours.{$day}.start_time")
                                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td>
                                                <input type="time" name="working_hours[{{ $day }}][end_time]" value="{{ old("working_hours.{$day}.end_time", '16:00') }}" class="form-control form-control-sm time-input-{{ $day }} @error("working_hours.{$day}.end_time") is-invalid @enderror" {{ $oldIsOpen == '1' ? '' : 'disabled' }}>
                                                @error("working_hours.{$day}.end_time")
                                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                                @enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    </div>{{-- end modal-body --}}

                    {{-- Modal Footer with Step Navigation --}}
                    <div class="modal-footer justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" id="btnPrevStep" style="display:none;">
                                <i class="fas fa-arrow-left mr-1"></i> Back
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary mr-2" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="btnNextStep">
                                Next <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                            <button type="submit" class="btn btn-success" id="btnSubmit" style="display:none;">
                                <i class="fas fa-check mr-1"></i> Create Municipality
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        let currentStep = 1;
        const totalSteps = 3;

        function showStep(step) {
            currentStep = step;

            // Toggle step content
            for (let i = 1; i <= totalSteps; i++) {
                if (i === step) {
                    $('#step' + i + 'Content').show();
                } else {
                    $('#step' + i + 'Content').hide();
                }
            }

            // Update step tabs
            for (let i = 1; i <= totalSteps; i++) {
                const $tab = $('#step' + i + '-tab');
                const $badge = $tab.find('.badge');
                if (i === step) {
                    $tab.removeClass('disabled').addClass('active');
                    $badge.removeClass('badge-secondary').addClass('badge-primary');
                } else if (i < step) {
                    $tab.removeClass('disabled active');
                    $badge.removeClass('badge-secondary').addClass('badge-success');
                } else {
                    $tab.removeClass('active').addClass('disabled');
                    $badge.removeClass('badge-primary badge-success').addClass('badge-secondary');
                }
            }

            // Toggle navigation buttons
            if (step > 1) { $('#btnPrevStep').show(); } else { $('#btnPrevStep').hide(); }
            if (step < totalSteps) { $('#btnNextStep').show(); } else { $('#btnNextStep').hide(); }
            if (step === totalSteps) { $('#btnSubmit').show(); } else { $('#btnSubmit').hide(); }

            // Update Next button text based on current step
            if (step === 1) {
                $('#btnNextStep').html('Next: Location <i class="fas fa-arrow-right ml-1"></i>');
            } else if (step === 2) {
                $('#btnNextStep').html('Next: Working Hours <i class="fas fa-arrow-right ml-1"></i>');
            }

            // Scroll modal body to top
            $('#createMunicipalityModal .modal-body').first().animate({ scrollTop: 0 }, 200);
        }

        // Helper: clear validation errors from a step
        function clearStepErrors(step) {
            var $container = $('#step' + step + 'Content');
            $container.find('.is-invalid').removeClass('is-invalid');
            $container.find('.invalid-feedback').remove();
            $container.find('.ajax-error-msg').remove();
        }

        // Helper: show server validation errors on fields
        function showStepErrors(step, errors) {
            clearStepErrors(step);
            var $container = $('#step' + step + 'Content');
            $.each(errors, function(field, messages) {
                var inputName = field.replace(/\./g, '.');
                var $input = $container.find('[name="' + inputName + '"]');
                if ($input.length) {
                    $input.addClass('is-invalid');
                    // Remove existing feedback
                    $input.siblings('.invalid-feedback').remove();
                    $input.after('<span class="invalid-feedback d-block">' + messages[0] + '</span>');
                } else {
                    // Field not found in this step, show at top
                    if (!$container.find('.ajax-error-msg').length) {
                        $container.prepend('<div class="ajax-error-msg alert alert-danger"></div>');
                    }
                    $container.find('.ajax-error-msg').append('<p class="mb-1"><strong>' + field + ':</strong> ' + messages[0] + '</p>');
                }
            });
        }

        // Validate a step via AJAX, returns promise
        function validateStepAjax(step) {
            return new Promise(function(resolve, reject) {
                var formData = $('#createMunicipalityForm').serialize();
                formData += '&step=' + step;
                $.ajax({
                    url: '{{ route("admin.municipalities.validate-step") }}',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function() {
                        clearStepErrors(step);
                        resolve(true);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            showStepErrors(step, errors);
                            resolve(false);
                        } else {
                            resolve(false);
                        }
                    }
                });
            });
        }

        $('#btnNextStep').on('click', function() {
            if (currentStep < totalSteps) {
                var btn = $(this);
                btn.prop('disabled', true);
                validateStepAjax(currentStep).then(function(valid) {
                    btn.prop('disabled', false);
                    if (!valid) return;

                    var nextStep = currentStep + 1;
                    showStep(nextStep);
                    // Initialize map when navigating to step 2
                    if (nextStep === 2 && typeof initMunicipalityMap === 'function' && !window.mapInitialized) {
                        initMunicipalityMap();
                        window.mapInitialized = true;
                    }
                });
            }
        });

        // Prevent Enter key from submitting the form
        $('#createMunicipalityForm').on('keydown', function(e) {
            if (e.key === 'Enter' && currentStep < totalSteps) {
                e.preventDefault();
                $('#btnNextStep').click();
            }
        });

        // Intercept form submit: validate step 3 first, then submit if valid
        $('#createMunicipalityForm').on('submit', function(e) {
            if (!$(this).data('validated')) {
                e.preventDefault();
                var btn = $('#btnSubmit');
                btn.prop('disabled', true);
                validateStepAjax(3).then(function(valid) {
                    btn.prop('disabled', false);
                    if (valid) {
                        $('#createMunicipalityForm').data('validated', true);
                        $('#createMunicipalityForm').submit();
                    }
                });
            }
        });

        $('#btnPrevStep').on('click', function() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });

        // Step tab clicks
        $('#step1-tab').on('click', function(e) {
            e.preventDefault();
            showStep(1);
        });
        $('#step2-tab').on('click', function(e) {
            e.preventDefault();
            if (currentStep >= 2) showStep(2);
        });
        $('#step3-tab').on('click', function(e) {
            e.preventDefault();
            if (currentStep >= 3) showStep(3);
        });

        // Day toggle for working hours
        $(document).on('change', '.day-toggle', function() {
            const day = $(this).attr('id').replace('open_', '');
            const isChecked = $(this).is(':checked');
            $('.time-input-' + day).prop('disabled', !isChecked);
        });

        // Reset wizard when modal is closed
        $('#createMunicipalityModal').on('hidden.bs.modal', function() {
            showStep(1);
        });

        // Re-open modal on validation errors
        @if ($errors->any() && old('_token'))
            $(function () {
                const step1Fields = ['name', 'phone', 'email', 'city', 'street', 'building', 'status', 'notes'];
                const step2Fields = ['google_maps_url', 'latitude', 'longitude', 'place_id', 'formatted_address'];
                const errorKeys = Object.keys({!! json_encode($errors->messages()) !!});
                const hasStep2Errors = errorKeys.some(key => step2Fields.includes(key));
                const hasStep3Errors = errorKeys.some(key => !step1Fields.includes(key) && !step2Fields.includes(key));

                if (hasStep3Errors) {
                    showStep(3);
                } else if (hasStep2Errors) {
                    showStep(2);
                } else {
                    showStep(1);
                }
                $('#createMunicipalityModal').modal('show');
            });
        @endif
    </script>

    @if($mapsKey)
        <script>
            function initMunicipalityMap() {
                const defaultCenter = { lat: 33.8547, lng: 35.8623 };
                const savedLat = parseFloat(document.getElementById('latitude').value);
                const savedLng = parseFloat(document.getElementById('longitude').value);
                const hasSavedLocation = !Number.isNaN(savedLat) && !Number.isNaN(savedLng);
                const center = hasSavedLocation ? { lat: savedLat, lng: savedLng } : defaultCenter;

                const map = new google.maps.Map(document.getElementById('municipality_map'), {
                    center: center,
                    zoom: hasSavedLocation ? 15 : 8,
                    clickableIcons: false,
                });

                const marker = new google.maps.Marker({
                    map: map,
                    position: hasSavedLocation ? center : null,
                });

                const geocoder = new google.maps.Geocoder();
                const mapsUrlInput = document.getElementById('google_maps_url_input');
                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');
                const placeIdInput = document.getElementById('place_id');
                const addressInput = document.getElementById('formatted_address');
                const addressDisplay = document.getElementById('selected_address_display');

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

                const searchInput = document.getElementById('map_search');
                if (searchInput) {
                    const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                        fields: ['formatted_address', 'geometry', 'name', 'place_id', 'url'],
                    });
                    autocomplete.bindTo('bounds', map);
                    autocomplete.addListener('place_changed', function() {
                        const place = autocomplete.getPlace();
                        if (!place.geometry || !place.geometry.location) return;
                        const location = place.geometry.location;
                        const latitude = location.lat();
                        const longitude = location.lng();
                        const address = place.formatted_address || place.name || '';
                        const mapsUrl = place.url || googleMapsUrl(latitude, longitude);
                        moveMarker(latitude, longitude);
                        updateSelectedLocation(latitude, longitude, place.place_id || '', address, mapsUrl);
                    });

                    // Search button click - geocode the text input
                    const searchBtn = document.getElementById('map_search_btn');
                    if (searchBtn) {
                        searchBtn.addEventListener('click', function() {
                            const query = searchInput.value.trim();
                            if (!query) return;
                            geocoder.geocode({ address: query }, function(results, status) {
                                if (status === 'OK' && results[0]) {
                                    const location = results[0].geometry.location;
                                    const latitude = location.lat();
                                    const longitude = location.lng();
                                    const address = results[0].formatted_address || '';
                                    moveMarker(latitude, longitude);
                                    updateSelectedLocation(
                                        latitude, longitude,
                                        results[0].place_id || '',
                                        address,
                                        googleMapsUrl(latitude, longitude)
                                    );
                                } else {
                                    alert('Location not found. Try a different search term.');
                                }
                            });
                        });
                    }
                }

                map.addListener('click', function(event) {
                    const latitude = event.latLng.lat();
                    const longitude = event.latLng.lng();
                    moveMarker(latitude, longitude);
                    updateSelectedLocation(latitude, longitude, '', '', googleMapsUrl(latitude, longitude));
                    geocoder.geocode({ location: event.latLng }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            updateSelectedLocation(
                                latitude, longitude,
                                results[0].place_id || '',
                                results[0].formatted_address || '',
                                googleMapsUrl(latitude, longitude)
                            );
                        }
                    });
                });
            }
        </script>
        {{-- Load Maps API lazily - only when modal opens --}}
        <script>
            let mapsApiLoaded = false;
            $('#createMunicipalityModal').on('shown.bs.modal', function () {
                if (!mapsApiLoaded) {
                    const script = document.createElement('script');
                    script.src = 'https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initMunicipalityMap';
                    script.async = true;
                    script.defer = true;
                    document.head.appendChild(script);
                    mapsApiLoaded = true;
                    window.mapInitialized = true;
                } else if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                    initMunicipalityMap();
                }
            });
        </script>
    @endif

    {{-- DataTables --}}
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    {{-- DataTables --}}
    <script src="{{ asset('assets/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Initialize DataTable
        $('#municipalitiesTable').DataTable({
            responsive: true,
            autoWidth: false,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            pageLength: 10,
            language: {
                search: '<i class="fas fa-search"></i>',
                searchPlaceholder: 'Search municipalities...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ municipalities',
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: -1 } // Disable sorting on Actions column
            ]
        });

        // Toastr config
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
            extendedTimeOut: 2000,
        };

        // Show session messages as toastr
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif

        // SweetAlert2 delete confirmation
        $(document).on('click', '.btn-delete-municipality', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');

            Swal.fire({
                title: 'Delete Municipality?',
                html: 'Are you sure you want to delete <strong>' + name + '</strong>?<br>This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit delete form
                    var form = $('<form>', {
                        method: 'POST',
                        action: url
                    });
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_method',
                        value: 'DELETE'
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    </script>
@endpush
