@extends('layouts.municipality')

@section('title', 'Edit Office Profile')
@section('page-title', 'Edit Office Profile')

@section('content')
    <form method="POST" action="{{ route('municipality.office.update') }}">
        @csrf
        @method('PUT')

        {{-- Header --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-pen mr-1"></i> Edit: {{ $office->name }}</h3>
                <a href="{{ route('municipality.office.show') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Profile
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Office Information --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building mr-1"></i> Office Information</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Office Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $office->name) }}" class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Municipality</label>
                            <input type="text" value="{{ $office->municipality ? $office->municipality->name : 'Not assigned' }}" class="form-control" disabled>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Service Type</label>
                            <select name="service_type" class="form-control @error('service_type') is-invalid @enderror">
                                <option value="">Select Service Type</option>
                                @foreach (['Civil Registry', 'Tax Collection', 'Building Permits', 'Public Works', 'Complaints', 'General Services', 'Other'] as $serviceType)
                                    <option value="{{ $serviceType }}" {{ old('service_type', $office->service_type) === $serviceType ? 'selected' : '' }}>
                                        {{ $serviceType }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_type')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $office->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+961 ...">
                            @error('phone')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $office->email) }}" class="form-control @error('email') is-invalid @enderror" placeholder="office@example.com">
                            @error('email')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Location --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> Location</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="{{ old('city', $office->city) }}" class="form-control @error('city') is-invalid @enderror">
                            @error('city')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Street</label>
                            <input type="text" name="street" value="{{ old('street', $office->street) }}" class="form-control @error('street') is-invalid @enderror">
                            @error('street')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Building</label>
                            <input type="text" name="building" value="{{ old('building', $office->building) }}" class="form-control @error('building') is-invalid @enderror">
                            @error('building')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Google Maps URL</label>
                            <input type="url" name="google_maps_url" value="{{ old('google_maps_url', $office->google_maps_url) }}" class="form-control @error('google_maps_url') is-invalid @enderror" placeholder="https://maps.google.com/...">
                            @error('google_maps_url')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Formatted Address</label>
                            <input type="text" name="formatted_address" value="{{ old('formatted_address', $office->formatted_address) }}" class="form-control @error('formatted_address') is-invalid @enderror">
                            @error('formatted_address')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="latitude" value="{{ old('latitude', $office->latitude) }}" class="form-control @error('latitude') is-invalid @enderror" placeholder="33.8938">
                            @error('latitude')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="longitude" value="{{ old('longitude', $office->longitude) }}" class="form-control @error('longitude') is-invalid @enderror" placeholder="35.5018">
                            @error('longitude')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Place ID</label>
                            <input type="text" name="place_id" value="{{ old('place_id', $office->place_id) }}" class="form-control @error('place_id') is-invalid @enderror">
                            @error('place_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sticky-note mr-1"></i> Notes</h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Add any notes about this office...">{{ old('notes', $office->notes) }}</textarea>
                    @error('notes')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Working Hours --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Working Hours</h3>
            </div>
            <div class="card-body">
                @php
                    $whErrors = array_filter($errors->keys(), fn($key) => str_starts_with($key, 'working_hours'));
                @endphp
                @if(count($whErrors) > 0)
                    <div class="alert alert-danger mb-3">
                        <i class="fas fa-exclamation-circle mr-1"></i> Please fix the working hours errors below.
                    </div>
                @endif

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Open?</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($days as $day)
                            @php
                                $workingHour = $workingHours[$day];
                                $oldIsOpen = old("working_hours.{$day}.is_open", $workingHour->is_open ? '1' : '0');
                                $oldStart = old("working_hours.{$day}.start_time", $workingHour->start_time ? substr($workingHour->start_time, 0, 5) : '08:00');
                                $oldEnd = old("working_hours.{$day}.end_time", $workingHour->end_time ? substr($workingHour->end_time, 0, 5) : '14:00');
                            @endphp
                            <tr>
                                <td><strong>{{ $day }}</strong></td>
                                <td>
                                    <input type="hidden" name="working_hours[{{ $day }}][is_open]" value="0">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                            class="custom-control-input day-toggle"
                                            data-day="{{ $day }}"
                                            id="wh_open_{{ $day }}"
                                            name="working_hours[{{ $day }}][is_open]"
                                            value="1"
                                            {{ $oldIsOpen == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="wh_open_{{ $day }}">
                                            {{ $oldIsOpen == '1' ? 'Open' : 'Closed' }}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="time"
                                        name="working_hours[{{ $day }}][start_time]"
                                        value="{{ $oldStart }}"
                                        class="form-control form-control-sm time-start-{{ $day }} @error("working_hours.{$day}.start_time") is-invalid @enderror"
                                        style="max-width: 130px;"
                                        {{ $oldIsOpen == '1' ? '' : 'disabled' }}>
                                    @error("working_hours.{$day}.start_time")
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td>
                                    <input type="time"
                                        name="working_hours[{{ $day }}][end_time]"
                                        value="{{ $oldEnd }}"
                                        class="form-control form-control-sm time-end-{{ $day }} @error("working_hours.{$day}.end_time") is-invalid @enderror"
                                        style="max-width: 130px;"
                                        {{ $oldIsOpen == '1' ? '' : 'disabled' }}>
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

        {{-- Action Buttons --}}
        <div class="mb-4">
            <a href="{{ route('municipality.office.show') }}" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.day-toggle').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const day = this.dataset.day;
            const startInput = document.querySelector('.time-start-' + day);
            const endInput = document.querySelector('.time-end-' + day);
            const label = this.nextElementSibling;

            if (this.checked) {
                startInput.disabled = false;
                endInput.disabled = false;
                label.textContent = 'Open';
            } else {
                startInput.disabled = true;
                endInput.disabled = true;
                label.textContent = 'Closed';
            }
        });
    });
</script>
@endpush