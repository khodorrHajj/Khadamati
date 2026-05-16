@extends('layouts.municipality')

@section('title', 'Office Profile')
@section('page-title', 'Office Profile')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Office Header --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fas fa-building mr-1"></i> {{ $office->name }}
                <span class="badge {{ $office->status === 'active' ? 'badge-success' : 'badge-secondary' }} ml-2">
                    {{ ucfirst($office->status) }}
                </span>
            </h3>
            <a href="{{ route('municipality.office.edit') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-pen mr-1"></i> Edit Profile
            </a>
        </div>
        <div class="card-body">
            <div class="text-muted mb-0">
                @if($office->municipality)
                    <i class="fas fa-map-marker-alt mr-1"></i> {{ $office->municipality->name }}
                @endif
                @if($office->service_type)
                    &middot; <i class="fas fa-tag mr-1"></i> {{ $office->service_type }}
                @endif
            </div>
        </div>
    </div>

    {{-- Info Cards Row --}}
    <div class="row">
        {{-- Contact Information --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-address-card mr-1"></i> Contact Information</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th style="width:40%">Phone</th>
                            <td>
                                @if($office->phone)
                                    <a href="tel:{{ $office->phone }}">{{ $office->phone }}</a>
                                @else
                                    <span class="text-muted">Not added</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>
                                @if($office->email)
                                    <a href="mailto:{{ $office->email }}">{{ $office->email }}</a>
                                @else
                                    <span class="text-muted">Not added</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Service Type</th>
                            <td>{{ $office->service_type ?: 'Not added' }}</td>
                        </tr>
                        <tr>
                            <th>Municipality</th>
                            <td>{{ $office->municipality ? $office->municipality->name : 'Not assigned' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Location --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> Location</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th style="width:40%">City</th>
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
                        @if($office->formatted_address)
                            <tr>
                                <th>Address</th>
                                <td>{{ $office->formatted_address }}</td>
                            </tr>
                        @endif
                        @if($office->google_maps_url)
                            <tr>
                                <th>Maps</th>
                                <td><a href="{{ $office->google_maps_url }}" target="_blank" rel="noopener">Open in Google Maps</a></td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Map Preview --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map mr-1"></i> Map</h3>
                </div>
                <div class="card-body">
                    @if($office->latitude && $office->longitude)
                        <iframe
                            src="https://maps.google.com/maps?q={{ $office->latitude }},{{ $office->longitude }}&z=15&output=embed"
                            style="width:100%;height:220px;border:0;"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-map-marked-alt fa-2x mb-2 d-block"></i>
                            <p class="mb-0">No map location set</p>
                            <small>Add coordinates or a Google Maps URL in edit mode</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Working Hours & Notes Row --}}
    <div class="row">
        {{-- Working Hours --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Working Hours</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workingHours as $workingHour)
                                <tr>
                                    <td><strong>{{ $workingHour->day_of_week }}</strong></td>
                                    <td>
                                        @if($workingHour->is_open)
                                            <span class="badge badge-success">Open</span>
                                        @else
                                            <span class="badge badge-secondary">Closed</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($workingHour->is_open)
                                            {{ substr($workingHour->start_time, 0, 5) }} &ndash; {{ substr($workingHour->end_time, 0, 5) }}
                                        @else
                                            <span class="text-muted">Closed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-1"></i> Notes</h3>
                </div>
                <div class="card-body">
                    @if($office->notes)
                        <div style="white-space:pre-wrap;">{{ $office->notes }}</div>
                    @else
                        <p class="text-muted mb-0">No notes added yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection