@extends('layouts.municipality')

@section('title', 'Office Profile')
@section('page-title', 'Office Profile')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ $office->name }}</h3>
            <span class="badge {{ $office->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                {{ ucfirst($office->status) }}
            </span>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">Office Name</th>
                                <td>{{ $office->name }}</td>
                            </tr>
                            <tr>
                                <th>Municipality</th>
                                <td>{{ $office->municipality ? $office->municipality->name : 'Not assigned' }}</td>
                            </tr>
                            <tr>
                                <th>Service Type</th>
                                <td>{{ $office->service_type ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $office->phone ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>
                                    @if($office->email)
                                        <a href="mailto:{{ $office->email }}">{{ $office->email }}</a>
                                    @else
                                        Not added
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ ucfirst($office->status) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">City</th>
                                <td>{{ $office->city ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Street</th>
                                <td>{{ $office->street ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Building</th>
                                <td>{{ $office->building ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Google Maps URL</th>
                                <td>
                                    @if($office->google_maps_url)
                                        <a href="{{ $office->google_maps_url }}" target="_blank" rel="noopener">Open Map</a>
                                    @else
                                        Not added
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Latitude</th>
                                <td>{{ $office->latitude ?: 'Not added' }}</td>
                            </tr>
                            <tr>
                                <th>Longitude</th>
                                <td>{{ $office->longitude ?: 'Not added' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Notes</h3>
                </div>
                <div class="card-body">
                    {{ $office->notes ?: 'No notes added.' }}
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Working Hours</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workingHours as $workingHour)
                                <tr>
                                    <td>{{ $workingHour->day_of_week }}</td>
                                    <td>
                                        <span class="badge {{ $workingHour->is_open ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $workingHour->is_open ? 'Open' : 'Closed' }}
                                        </span>
                                    </td>
                                    <td>{{ $workingHour->is_open ? ($workingHour->start_time ?: '-') : '-' }}</td>
                                    <td>{{ $workingHour->is_open ? ($workingHour->end_time ?: '-') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('municipality.office.edit') }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Office Profile
                </a>
            </div>
        </div>
    </div>
@endsection
