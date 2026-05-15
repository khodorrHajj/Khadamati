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
                            <tr>
                                <th>Coordinates</th>
                                <td>
                                    @if ($office->hasCoordinates())
                                        {{ $office->latitude }}, {{ $office->longitude }}
                                    @else
                                        <span class="text-muted">No coordinates available.</span>
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

                    @if ($office->hasCoordinates())
                        <div class="mt-4">
                            <h4 class="h6">Map Preview</h4>
                            <div class="border rounded overflow-hidden">
                                <iframe
                                    title="Map preview for {{ $office->name }}"
                                    src="https://www.google.com/maps?q={{ $office->latitude }},{{ $office->longitude }}&z=15&output=embed"
                                    width="100%"
                                    height="260"
                                    style="border:0;"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
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
                            <td>{{ $service->formattedPrice() }}</td>
                            <td>{{ $service->durationLabel() }}</td>
                            <td>{{ implode(', ', $service->requiredDocumentList()) ?: 'No required documents listed.' }}</td>
                            <td>
                                <a href="{{ route('citizen.services.request.create', $service) }}" class="btn btn-primary btn-sm">
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

    @if ($categories->isNotEmpty())
        <div class="alert alert-light border">
            {{ $categories->count() }} service categor{{ $categories->count() === 1 ? 'y is' : 'ies are' }} available in this office.
            Use the service list above or the office service filters to find what you need quickly.
        </div>
    @endif
@endsection
