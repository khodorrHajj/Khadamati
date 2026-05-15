@extends('layouts.admin')

@section('title', 'Requests')
@section('page-title', 'Manage Requests')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Search Bar --}}
    <div class="card">
        <div class="card-body pb-2">
            <form method="GET" action="{{ route('admin.requests.index') }}" id="admin-requests-filter-form">
                <div class="row align-items-end">
                    <div class="col-lg-5 col-md-6">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold">Search Requests</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Tracking code, citizen name, or email...">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group mb-2">
                            <label>Date Range</label>
                            <div class="input-group">
                                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" placeholder="From">
                                <div class="input-group-prepend input-group-append">
                                    <span class="input-group-text">to</span>
                                </div>
                                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" placeholder="To">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group mb-2">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                @if (collect($filters)->filter()->isNotEmpty())
                                    <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary ml-2">
                                        <i class="fas fa-times"></i> Clear All
                                    </a>
                                @endif
                                <button type="button" class="btn btn-outline-secondary ml-2" data-toggle="collapse" data-target="#advancedFilters" aria-expanded="false">
                                    <i class="fas fa-sliders-h"></i> Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Filter Pills --}}
                <div class="d-flex flex-wrap mb-2">
                    <a href="{{ route('admin.requests.index', ['workflow_state' => \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN]) }}"
                       class="btn btn-sm mr-1 mb-1 {{ ($filters['workflow_state'] ?? '') === \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN ? 'btn-danger' : 'btn-outline-danger' }}">
                        <i class="fas fa-user-shield"></i> Awaiting Admin
                    </a>
                    <a href="{{ route('admin.requests.index', ['assignment_scope' => 'unassigned']) }}"
                       class="btn btn-sm mr-1 mb-1 {{ ($filters['assignment_scope'] ?? '') === 'unassigned' ? 'btn-warning' : 'btn-outline-warning' }}">
                        <i class="fas fa-user-clock"></i> Unassigned
                    </a>
                    <a href="{{ route('admin.requests.index', ['assignment_scope' => 'escalated']) }}"
                       class="btn btn-sm mr-1 mb-1 {{ ($filters['assignment_scope'] ?? '') === 'escalated' ? 'btn-info' : 'btn-outline-info' }}">
                        <i class="fas fa-exclamation-circle"></i> Escalated
                    </a>
                    <a href="{{ route('admin.requests.index', ['date_from' => now()->subDays(7)->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}"
                       class="btn btn-sm mr-1 mb-1 {{ (request()->has('date_from') && request()->has('date_to')) ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        <i class="fas fa-calendar-week"></i> Last 7 Days
                    </a>
                </div>

                {{-- Active Filter Chips --}}
                @if (collect($filters)->filter()->isNotEmpty())
                    <div class="d-flex flex-wrap align-items-center mb-2">
                        <span class="text-muted small mr-2">Active filters:</span>
                        @foreach ($filters as $key => $value)
                            @if (!empty($value))
                                @php
                                    $label = ucfirst(str_replace('_', ' ', $key));
                                    if ($key === 'municipality') {
                                        $m = $municipalities->firstWhere('id', $value);
                                        $displayValue = $m ? $m->name : $value;
                                    } elseif ($key === 'office') {
                                        $o = $offices->firstWhere('id', $value);
                                        $displayValue = $o ? $o->name : $value;
                                    } elseif ($key === 'service') {
                                        $s = $services->firstWhere('id', $value);
                                        $displayValue = $s ? $s->name : $value;
                                    } elseif ($key === 'category') {
                                        $c = $categories->firstWhere('id', $value);
                                        $displayValue = $c ? $c->name : $value;
                                    } elseif ($key === 'assigned_to_user_id') {
                                        $u = $municipalityUsers->firstWhere('id', $value);
                                        $displayValue = $u ? $u->name : $value;
                                    } elseif ($key === 'workflow_state') {
                                        $displayValue = $value === \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN ? 'Awaiting Admin' : 'Awaiting Municipality';
                                    } elseif ($key === 'assignment_scope') {
                                        $displayValue = ucfirst($value);
                                    } else {
                                        $displayValue = Str::limit($value, 25);
                                    }
                                @endphp
                                <a href="{{ request()->fullUrlWithQuery([$key => null]) }}" class="badge badge-primary mr-1 mb-1" style="font-size: 0.85em; padding: 5px 10px;">
                                    {{ $label }}: {{ $displayValue }}
                                    <i class="fas fa-times ml-1"></i>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Advanced Filters (Collapsible) --}}
                <div class="collapse" id="advancedFilters">
                    <div class="card card-outline card-secondary mt-2 mb-0">
                        <div class="card-header with-border">
                            <h6 class="card-title mb-0">Advanced Filters</h6>
                        </div>
                        <div class="card-body">
                            {{-- Request State Group --}}
                            <div class="mb-3">
                                <small class="text-muted text-uppercase font-weight-bold d-block mb-2">
                                    <i class="fas fa-flag mr-1"></i> Request State
                                </small>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="small">Status</label>
                                            <select name="status" class="custom-select custom-select-sm">
                                                <option value="">All Statuses</option>
                                                @foreach ($statuses as $status)
                                                    <option value="{{ $status }}" @selected((string) ($filters['status'] ?? '') === (string) $status)>
                                                        {{ $status }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="small">Workflow</label>
                                            <select name="workflow_state" class="custom-select custom-select-sm">
                                                <option value="">All Workflow States</option>
                                                @foreach ($workflowStates as $workflowState)
                                                    <option value="{{ $workflowState }}" @selected((string) ($filters['workflow_state'] ?? '') === (string) $workflowState)>
                                                        {{ $workflowState === \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN ? 'Awaiting Admin' : 'Awaiting Municipality' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="small">Assignment</label>
                                            <select name="assignment_scope" class="custom-select custom-select-sm">
                                                <option value="">All Assignment States</option>
                                                <option value="assigned" @selected(($filters['assignment_scope'] ?? '') === 'assigned')>Assigned</option>
                                                <option value="unassigned" @selected(($filters['assignment_scope'] ?? '') === 'unassigned')>Unassigned</option>
                                                <option value="escalated" @selected(($filters['assignment_scope'] ?? '') === 'escalated')>Escalated</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Organization Group --}}
                            <div class="mb-3">
                                <small class="text-muted text-uppercase font-weight-bold d-block mb-2">
                                    <i class="fas fa-sitemap mr-1"></i> Organization
                                </small>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="small">Municipality</label>
                                            <select name="municipality" class="custom-select custom-select-sm">
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
                                            <label class="small">Office</label>
                                            <select name="office" class="custom-select custom-select-sm">
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
                                            <label class="small">Service</label>
                                            <select name="service" class="custom-select custom-select-sm">
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
                                            <label class="small">Category</label>
                                            <select name="category" class="custom-select custom-select-sm">
                                                <option value="">All Categories</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- People Group --}}
                            <div>
                                <small class="text-muted text-uppercase font-weight-bold d-block mb-2">
                                    <i class="fas fa-users mr-1"></i> People
                                </small>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label class="small">Assigned Municipality User</label>
                                            <select name="assigned_to_user_id" class="custom-select custom-select-sm">
                                                <option value="">Any Municipality User</option>
                                                @foreach ($municipalityUsers as $municipalityUser)
                                                    <option value="{{ $municipalityUser->id }}" @selected((string) ($filters['assigned_to_user_id'] ?? '') === (string) $municipalityUser->id)>
                                                        {{ $municipalityUser->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label class="small">Tracking Code</label>
                                            <input type="text" name="tracking_code" value="{{ $filters['tracking_code'] ?? '' }}" class="form-control form-control-sm" placeholder="REQ-">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Service Requests</h3>
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