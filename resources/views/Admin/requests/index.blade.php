@extends('layouts.admin')

@section('title', 'Requests')
@section('page-title', 'Manage Requests')

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
            <h3 class="card-title">Request Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.requests.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="custom-select">
                                <option value="">All Statuses</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected((string) ($filters['status'] ?? '') === (string) $status)>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Workflow</label>
                            <select name="workflow_state" class="custom-select">
                                <option value="">All Workflow States</option>
                                @foreach ($workflowStates as $workflowState)
                                    <option value="{{ $workflowState }}" @selected((string) ($filters['workflow_state'] ?? '') === (string) $workflowState)>
                                        {{ $workflowState === \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN ? 'Awaiting Admin' : 'Awaiting Municipality' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Municipality</label>
                            <select name="municipality" class="custom-select">
                                <option value="">All Municipalities</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" @selected((string) ($filters['municipality'] ?? '') === (string) $municipality->id)>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Assignment</label>
                            <select name="assignment_scope" class="custom-select">
                                <option value="">All Assignment States</option>
                                <option value="assigned" @selected(($filters['assignment_scope'] ?? '') === 'assigned')>Assigned</option>
                                <option value="unassigned" @selected(($filters['assignment_scope'] ?? '') === 'unassigned')>Unassigned</option>
                                <option value="escalated" @selected(($filters['assignment_scope'] ?? '') === 'escalated')>Escalated</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Office</label>
                            <select name="office" class="custom-select">
                                <option value="">All Offices</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" @selected((string) ($filters['office'] ?? '') === (string) $office->id)>
                                        {{ $office->name }}
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
                                    <option value="{{ $service->id }}" @selected((string) ($filters['service'] ?? '') === (string) $service->id)>
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
                                    <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Assigned Municipality User</label>
                            <select name="assigned_to_user_id" class="custom-select">
                                <option value="">Any Municipality User</option>
                                @foreach ($municipalityUsers as $municipalityUser)
                                    <option value="{{ $municipalityUser->id }}" @selected((string) ($filters['assigned_to_user_id'] ?? '') === (string) $municipalityUser->id)>
                                        {{ $municipalityUser->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tracking Code</label>
                            <input type="text" name="tracking_code" value="{{ $filters['tracking_code'] ?? '' }}" class="form-control" placeholder="REQ-">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>From</label>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>To</label>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label>Citizen Search</label>
                            <div class="input-group">
                                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search by citizen name or email">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                    @if (collect($filters)->filter()->isNotEmpty())
                                        <a href="{{ route('admin.requests.index') }}" class="btn btn-secondary">Clear</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Service Requests</h3>
            <div class="card-tools">
                <span class="text-muted small" id="admin-requests-poll-status">Auto-refreshing every 5 seconds</span>
            </div>
        </div>
        <div id="admin-requests-polling-root" data-poll-url="{{ route('admin.requests.poll', request()->query()) }}">
            <div class="card-body table-responsive p-0" id="admin-requests-table">
                @include('Admin.requests._table', ['requests' => $requests])
            </div>
            <div id="admin-requests-pagination">
                @include('Admin.requests._pagination', ['requests' => $requests])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.getElementById('admin-requests-polling-root');
            const tableContainer = document.getElementById('admin-requests-table');
            const paginationContainer = document.getElementById('admin-requests-pagination');
            const statusLabel = document.getElementById('admin-requests-poll-status');

            if (!root || !tableContainer || !paginationContainer) {
                return;
            }

            const pollUrl = root.dataset.pollUrl;
            let polling = false;

            const updateStatus = (message) => {
                if (statusLabel) {
                    statusLabel.textContent = message;
                }
            };

            const refreshRequests = async () => {
                if (polling || document.hidden) {
                    return;
                }

                polling = true;

                try {
                    const response = await fetch(pollUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Polling failed.');
                    }

                    const payload = await response.json();

                    tableContainer.innerHTML = payload.table_html;
                    paginationContainer.innerHTML = payload.pagination_html;
                    updateStatus('Last checked just now');
                } catch (error) {
                    updateStatus('Auto-refresh paused. Refresh the page to retry.');
                } finally {
                    polling = false;
                }
            };

            setInterval(refreshRequests, 5000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    refreshRequests();
                }
            });
        })();
    </script>
@endpush
