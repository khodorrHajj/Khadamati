@extends('layouts.municipality')

@section('title', 'Edit Office Profile')
@section('page-title', 'Edit Office Profile')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Edit: {{ $office->name }}</h3>
            <a href="{{ route('municipality.office.show') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('municipality.office.update') }}">
                @csrf
                @method('PUT')

                <h5 class="mb-3 border-bottom pb-2">Office Information</h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Municipality</label>
                            <input type="text" value="{{ $office->municipality ? $office->municipality->name : 'Not assigned' }}" class="form-control" disabled>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Office Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $office->name) }}" class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $office->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $office->email) }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <h5 class="mb-3 mt-4 border-bottom pb-2">Location</h5>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="{{ old('city', $office->city) }}" class="form-control @error('city') is-invalid @enderror">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Street</label>
                            <input type="text" name="street" value="{{ old('street', $office->street) }}" class="form-control @error('street') is-invalid @enderror">
                            @error('street')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Building</label>
                            <input type="text" name="building" value="{{ old('building', $office->building) }}" class="form-control @error('building') is-invalid @enderror">
                            @error('building')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Google Maps URL</label>
                    <input type="url" name="google_maps_url" value="{{ old('google_maps_url', $office->google_maps_url) }}" class="form-control @error('google_maps_url') is-invalid @enderror" placeholder="https://maps.google.com/...">
                    @error('google_maps_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="latitude" value="{{ old('latitude', $office->latitude) }}" class="form-control @error('latitude') is-invalid @enderror">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="longitude" value="{{ old('longitude', $office->longitude) }}" class="form-control @error('longitude') is-invalid @enderror">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Place ID</label>
                            <input type="text" name="place_id" value="{{ old('place_id', $office->place_id) }}" class="form-control @error('place_id') is-invalid @enderror">
                            @error('place_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Formatted Address</label>
                    <input type="text" name="formatted_address" value="{{ old('formatted_address', $office->formatted_address) }}" class="form-control @error('formatted_address') is-invalid @enderror">
                    @error('formatted_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $office->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <h5 class="mb-3 mt-4 border-bottom pb-2">Working Hours</h5>

                @php
                    $whErrors = array_filter($errors->keys(), fn($key) => str_starts_with($key, 'working_hours'));
                @endphp
                @if(count($whErrors) > 0)
                    <div class="alert alert-danger">
                        @foreach($whErrors as $key)
                            <div>{{ $errors->first($key) }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 140px;">Day</th>
                                <th style="width: 100px;">Open</th>
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
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="hidden" name="working_hours[{{ $day }}][is_open]" value="0">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input day-toggle"
                                                id="open_{{ $day }}"
                                                name="working_hours[{{ $day }}][is_open]"
                                                value="1"
                                                {{ $oldIsOpen == '1' ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label" for="open_{{ $day }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            type="time"
                                            name="working_hours[{{ $day }}][start_time]"
                                            value="{{ $oldStart }}"
                                            class="form-control time-input-{{ $day }} @error("working_hours.{$day}.start_time") is-invalid @enderror"
                                            {{ $oldIsOpen == '1' ? '' : 'disabled' }}
                                        >
                                        @error("working_hours.{$day}.start_time")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input
                                            type="time"
                                            name="working_hours[{{ $day }}][end_time]"
                                            value="{{ $oldEnd }}"
                                            class="form-control time-input-{{ $day }} @error("working_hours.{$day}.end_time") is-invalid @enderror"
                                            {{ $oldIsOpen == '1' ? '' : 'disabled' }}
                                        >
                                        @error("working_hours.{$day}.end_time")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Office Profile
                    </button>
                    <a href="{{ route('municipality.office.show') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.day-toggle').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const day = this.id.replace('open_', '');
            document.querySelectorAll('.time-input-' + day).forEach(function(input) {
                input.disabled = !checkbox.checked;
                if (checkbox.checked) {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                }
            });
        });

        checkbox.dispatchEvent(new Event('change'));
    });
</script>
@endpush
