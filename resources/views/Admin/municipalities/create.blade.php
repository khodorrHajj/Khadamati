@extends('layouts.admin')

@section('title', 'Add Municipality')
@section('page-title', 'Add Municipality')

@section('content')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">New Municipality</h3>
            <a href="{{ route('admin.municipalities.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.municipalities.store') }}">
                @csrf

                {{-- Basic Info --}}
                <h5 class="mb-3 border-bottom pb-2">Basic Information</h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="03XXXXXX or +9613XXXXXX" required>
                            @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Location --}}
                <h5 class="mb-3 mt-4 border-bottom pb-2">Location</h5>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>City <span class="text-danger">*</span></label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                   class="form-control @error('city') is-invalid @enderror" required>
                            @error('city')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Street <span class="text-danger">*</span></label>
                            <input type="text" name="street" value="{{ old('street') }}"
                                   class="form-control @error('street') is-invalid @enderror" required>
                            @error('street')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Building</label>
                            <input type="text" name="building" value="{{ old('building') }}"
                                   class="form-control @error('building') is-invalid @enderror">
                            @error('building')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Google Maps URL</label>
                    <input type="url" name="google_maps_url" value="{{ old('google_maps_url') }}"
                           class="form-control @error('google_maps_url') is-invalid @enderror"
                           placeholder="https://maps.google.com/...">
                    @error('google_maps_url')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Status & Notes --}}
                <h5 class="mb-3 mt-4 border-bottom pb-2">Status & Notes</h5>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Working Hours --}}
                <h5 class="mb-3 mt-4 border-bottom pb-2">Working Hours</h5>
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
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:140px">Day</th>
                                <th style="width:100px">Open</th>
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
                                            <input
                                                type="hidden"
                                                name="working_hours[{{ $day }}][is_open]"
                                                value="0"
                                            >
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
                                            value="{{ old("working_hours.{$day}.start_time", '08:00') }}"
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
                                            value="{{ old("working_hours.{$day}.end_time", '14:00') }}"
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

                {{-- Buttons --}}
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Municipality
                    </button>
                    <a href="{{ route('admin.municipalities.index') }}" class="btn btn-secondary ml-2">
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
