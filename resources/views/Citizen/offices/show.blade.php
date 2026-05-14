@extends('layouts.citizen')

@section('title', 'Service Categories')
@section('page-title', $office->name)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Office Details</h3>
            <div class="card-tools">
                <a href="{{ route('citizen.services.index', ['office' => $office->id]) }}" class="btn btn-primary btn-sm">
                    Browse Office Services
                </a>
            </div>
        </div>
        <div class="card-body">
            @php
                $address = implode(', ', array_filter([
                    $office->building,
                    $office->street,
                    $office->city,
                    $office->address,
                ]));
            @endphp
            <div class="row">
                <div class="col-lg-6">
                    <table class="table table-bordered mb-lg-0">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">Office</th>
                                <td>{{ $office->name }}</td>
                            </tr>
                            <tr>
                                <th>Municipality</th>
                                <td>{{ $office->municipality?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Service Type</th>
                                <td>{{ $office->service_type ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td>{{ $address ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $office->phone ?: $office->contact_info ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $office->email ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Google Maps</th>
                                <td>
                                    @if ($office->google_maps_url)
                                        <a href="{{ $office->google_maps_url }}" target="_blank" rel="noopener">Open map</a>
                                    @else
                                        <span class="text-muted">No map link available.</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-6">
                    <h4 class="h6">Working Hours</h4>
                    @if ($office->workingHours->isNotEmpty())
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                @foreach ($office->workingHours as $workingHour)
                                    <tr>
                                        <th>{{ $workingHour->day_of_week }}</th>
                                        <td>
                                            @if ($workingHour->is_open)
                                                {{ $workingHour->start_time }} - {{ $workingHour->end_time }}
                                            @else
                                                Closed
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif ($office->working_hours)
                        <p class="mb-0">{{ $office->working_hours }}</p>
                    @else
                        <p class="text-muted mb-0">No working hours listed.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Available Services</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Required Documents</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->serviceCategory?->name ?? '-' }}</td>
                            <td>${{ number_format((float) $service->price, 2) }}</td>
                            <td>{{ $service->duration_days }} day{{ (int) $service->duration_days === 1 ? '' : 's' }}</td>
                            <td>{{ $service->required_documents ?: 'No required documents listed.' }}</td>
                            <td>
                                <a href="{{ route('citizen.services.show', $service) }}" class="btn btn-primary btn-sm">
                                    Start Request
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No active services are available for this office.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        @forelse ($categories as $category)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h5">{{ $category->name }}</h3>
                        <p>{{ $category->description ?: 'No category description available.' }}</p>
                        <p class="text-muted">{{ $category->active_services_count }} active service{{ $category->active_services_count === 1 ? '' : 's' }}</p>
                        <a href="{{ route('citizen.services.index', ['office' => $office->id, 'category' => $category->id]) }}" class="btn btn-outline-primary btn-sm">Browse Services</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">No active service categories are available for this office.</div>
            </div>
        @endforelse
    </div>
@endsection
