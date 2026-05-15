@extends('layouts.municipality')

@section('title', 'Incoming Requests')
@section('page-title', 'Incoming Service Requests')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Requests for {{ $office->name }}</h3>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('municipality.requests.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="custom-select">
                                <option value="">All Statuses</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Workflow</label>
                            <select name="handoff_scope" class="custom-select">
                                @foreach ($handoffScopes as $scopeValue => $scopeLabel)
                                    <option value="{{ $scopeValue }}" {{ ($filters['handoff_scope'] ?? 'all') === $scopeValue ? 'selected' : '' }}>
                                        {{ $scopeLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Service</label>
                            <select name="service" class="custom-select">
                                <option value="">All Services</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" {{ (string) $filters['service'] === (string) $service->id ? 'selected' : '' }}>
                                        {{ $service->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="custom-select">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) $filters['category'] === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Citizen Search</label>
                            <input
                                type="text"
                                name="search"
                                value="{{ old('search', $filters['search']) }}"
                                class="form-control"
                                placeholder="Name or email">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="btn-group mb-3">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            @if ($filters['status'] || $filters['service'] || $filters['category'] || $filters['date_from'] || $filters['date_to'] || $filters['search'] || (($filters['handoff_scope'] ?? 'all') !== 'all'))
                                <a href="{{ route('municipality.requests.index') }}" class="btn btn-secondary">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Citizen</th>
                        <th>Service</th>
                        <th>Progress</th>
                        <th style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $requestItem)
                        <tr>
                            <td>
                                <strong>#{{ $requestItem->id }}</strong>
                                <div class="text-muted small">{{ $requestItem->tracking_code }}</div>
                                <div class="text-muted small">{{ optional($requestItem->created_at)->format('Y-m-d H:i') }}</div>
                            </td>
                            <td>
                                <div>{{ $requestItem->user->name ?? 'Unknown Citizen' }}</div>
                                <div class="text-muted small">{{ $requestItem->user->email ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $requestItem->service->name ?? '-' }}</div>
                                <div class="text-muted small">{{ $requestItem->service && $requestItem->service->serviceCategory ? $requestItem->service->serviceCategory->name : '-' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-light border">{{ $requestItem->status }}</span>
                                @if ($requestItem->needsCitizenDocuments())
                                    <span class="badge badge-warning ml-1">Citizen Action Needed</span>
                                @elseif ($requestItem->isOverdue())
                                    <span class="badge badge-danger ml-1">Overdue</span>
                                @endif
                                <div class="small mt-1">
                                    @if ($requestItem->isClosed())
                                        <span class="badge badge-success">Closed</span>
                                    @elseif ($requestItem->isAwaitingAdmin())
                                        <span class="badge badge-danger">Awaiting Admin</span>
                                    @else
                                        <span class="badge badge-info">Awaiting Municipality</span>
                                    @endif
                                    @if ($requestItem->assignedTo)
                                        <div class="text-muted mt-1">Assigned to {{ $requestItem->assignedTo->name }}</div>
                                    @endif
                                </div>
                                <div class="text-muted small mt-2">
                                    {{ $requestItem->request_documents_count }} document{{ $requestItem->request_documents_count === 1 ? '' : 's' }}
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('municipality.requests.show', $requestItem) }}" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="card-footer">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
@endsection
