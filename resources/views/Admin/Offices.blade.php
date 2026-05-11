@extends('layouts.admin')

@section('title', 'Government Offices')
@section('page-title', 'Manage Government Offices')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Government Office</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.offices.store') }}">
                @csrf

                <div class="form-group">
                    <label>Municipality</label>
                    <select name="municipality_id" class="custom-select @error('municipality_id') is-invalid @enderror">
                        @foreach ($municipalities as $municipality)
                            <option value="{{ $municipality->id }}" {{ old('municipality_id') == $municipality->id ? 'selected' : '' }}>
                                {{ $municipality->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('municipality_id')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Office Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-control @error('address') is-invalid @enderror">
                    @error('address')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Google Maps Location</label>
                    <input type="text" name="google_maps_location" value="{{ old('google_maps_location') }}" class="form-control @error('google_maps_location') is-invalid @enderror">
                    @error('google_maps_location')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Working Hours</label>
                    <input type="text" name="working_hours" value="{{ old('working_hours') }}" class="form-control @error('working_hours') is-invalid @enderror">
                    @error('working_hours')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Contact Info</label>
                    <input type="text" name="contact_info" value="{{ old('contact_info') }}" class="form-control @error('contact_info') is-invalid @enderror">
                    @error('contact_info')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Existing Offices</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Office</th>
                        <th>Municipality</th>
                        <th>Address</th>
                        <th>Working Hours</th>
                        <th>Contact Info</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($offices as $office)
                        <tr>
                            <td>{{ $office->id }}</td>
                            <td>{{ $office->name }}</td>
                            <td>{{ $office->municipality->name }}</td>
                            <td>{{ $office->address }}</td>
                            <td>{{ $office->working_hours }}</td>
                            <td>{{ $office->contact_info }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
