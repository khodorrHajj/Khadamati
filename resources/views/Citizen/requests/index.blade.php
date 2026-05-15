@extends('layouts.citizen')

@section('title', 'My Requests')
@section('page-title', 'My Requests')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <form method="GET" action="{{ route('citizen.requests.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="service">Service</label>
                            <select name="service" id="service" class="form-control">
                                <option value="">All services</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" @selected((string) ($filters['service'] ?? '') === (string) $service->id)>{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="office">Office</label>
                            <select name="office" id="office" class="form-control">
                                <option value="">All offices</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" @selected((string) ($filters['office'] ?? '') === (string) $office->id)>{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">To</label>
                            <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="tracking_code">Tracking Code</label>
                            <input type="text" name="tracking_code" id="tracking_code" value="{{ $filters['tracking_code'] ?? '' }}" class="form-control" placeholder="REQ-">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('citizen.requests.index') }}" class="btn btn-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Requests</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Tracking Code</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $serviceRequest)
                        <tr>
                            <td>#{{ $serviceRequest->id }}</td>
                            <td>{{ $serviceRequest->tracking_code }}</td>
                            <td>{{ $serviceRequest->service?->name ?? '-' }}</td>
                            <td>{{ $serviceRequest->service?->governmentOffice?->name ?? '-' }}</td>
                            <td><span class="badge badge-light border">{{ $serviceRequest->status }}</span></td>
                            <td>{{ optional($serviceRequest->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                            <td>
                                <a href="{{ route('citizen.requests.show', $serviceRequest) }}" class="btn btn-primary btn-sm">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No requests match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($requests->hasPages())
        <div class="mt-3">
            {{ $requests->links() }}
        </div>
    @endif
@endsection
