@extends('layouts.admin')

@section('title', 'Add Government Office')
@section('page-title', 'Add Government Office')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">New Government Office</h3>
            <a href="{{ route('admin.offices.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.offices.store') }}">
                @csrf

                <h5 class="mb-3 border-bottom pb-2">Office Information</h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Municipality <span class="text-danger">*</span></label>
                            <select name="municipality_id" class="form-control @error('municipality_id') is-invalid @enderror">
                                <option value="">Select Municipality</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" {{ old('municipality_id') == $municipality->id ? 'selected' : '' }}>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('municipality_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Office Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
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
                                    <option value="{{ $serviceType }}" {{ old('service_type') === $serviceType ? 'selected' : '' }}>
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
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
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
                            <input type="text" name="city" value="{{ old('city') }}" class="form-control @error('city') is-invalid @enderror">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Street</label>
                            <input type="text" name="street" value="{{ old('street') }}" class="form-control @error('street') is-invalid @enderror">
                            @error('street')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Building</label>
                            <input type="text" name="building" value="{{ old('building') }}" class="form-control @error('building') is-invalid @enderror">
                            @error('building')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Google Maps URL</label>
                    <input type="url" name="google_maps_url" value="{{ old('google_maps_url') }}" class="form-control @error('google_maps_url') is-invalid @enderror" placeholder="https://maps.google.com/...">
                    @error('google_maps_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @include('Admin.offices._map_picker')

                <h5 class="mb-3 mt-4 border-bottom pb-2">Status & Notes</h5>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
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
                                    $defaults = $workingHours[$day] ?? ['is_open' => '0', 'start_time' => '08:00', 'end_time' => '14:00'];
                                    $oldIsOpen = old("working_hours.{$day}.is_open", $defaults['is_open']);
                                    $oldStart = old("working_hours.{$day}.start_time", $defaults['start_time']);
                                    $oldEnd = old("working_hours.{$day}.end_time", $defaults['end_time']);
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
                        <i class="fas fa-save"></i> Save Government Office
                    </button>
                    <a href="{{ route('admin.offices.index') }}" class="btn btn-secondary ml-2">
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
