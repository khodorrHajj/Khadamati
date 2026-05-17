@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
    <div data-admin-live-region="admin-dashboard" data-admin-live-init="dashboard">
    {{-- Welcome Row --}}
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tachometer-alt mr-1"></i> Welcome</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Welcome, <strong>{{ Auth::user()->name }}</strong></p>
                    <p class="mb-0 text-muted">Use the admin tools below to manage municipalities, government offices, and municipality users.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i> Role</h3>
                </div>
                <div class="card-body">
                    <span class="badge badge-primary badge-lg p-2">{{ Auth::user()->role->role }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Platform Overview Stat Cards --}}
    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $municipalityCount }}</h3>
                    <p>Municipalities</p>
                </div>
                <div class="icon"><i class="fas fa-city"></i></div>
                <a href="{{ route('admin.municipalities.index') }}" class="small-box-footer">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $officeCount }}</h3>
                    <p>Government Offices</p>
                </div>
                <div class="icon"><i class="fas fa-building"></i></div>
                <a href="{{ route('admin.offices.index') }}" class="small-box-footer">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3>{{ $serviceCount }}</h3>
                    <p>Services</p>
                </div>
                <div class="icon"><i class="fas fa-concierge-bell"></i></div>
                <a href="{{ route('admin.services.index') }}" class="small-box-footer">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Users & Requests Stat Cards --}}
    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $municipalityUserCount }}</h3>
                    <p>Municipality Users</p>
                </div>
                <div class="icon"><i class="fas fa-users-cog"></i></div>
                <a href="{{ route('admin.municipality.users') }}" class="small-box-footer">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $citizenCount }}</h3>
                    <p>Citizens</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="{{ route('admin.citizens.index') }}" class="small-box-footer">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $requestStats['total'] }}</h3>
                    <p>Total Requests</p>
                </div>
                <div class="icon"><i class="fas fa-file-signature"></i></div>
                <a href="{{ route('admin.requests.index') }}" class="small-box-footer">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Request Alerts --}}
    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $requestStats['awaitingAdmin'] }}</h3>
                    <p>Awaiting Admin</p>
                </div>
                <div class="icon"><i class="fas fa-user-shield"></i></div>
                <a href="{{ route('admin.requests.index', ['workflow_state' => \App\Models\ServiceRequest::WORKFLOW_AWAITING_ADMIN]) }}" class="small-box-footer">
                    Review <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-dark">
                <div class="inner">
                    <h3>{{ $requestStats['overdue'] }}</h3>
                    <p>Overdue Requests</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <a href="{{ route('admin.requests.index') }}" class="small-box-footer">
                    Review <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3>{{ $requestStats['unassigned'] }}</h3>
                    <p>Unassigned Requests</p>
                </div>
                <div class="icon"><i class="fas fa-user-clock"></i></div>
                <a href="{{ route('admin.requests.index', ['assignment_scope' => 'unassigned']) }}" class="small-box-footer">
                    Assign <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        {{-- Requests Trend (last 30 days) --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Requests Trend (Last 30 Days)</h3>
                </div>
                <div class="card-body">
                    <canvas id="requestsTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Status Distribution</h3>
                </div>
                <div class="card-body d-flex justify-content-center">
                    <canvas id="statusDistChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue & Top Municipalities Row --}}
    <div class="row">
        {{-- Revenue Trend --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-dollar-sign mr-1"></i> Revenue Trend (Last 30 Days)</h3>
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Top Municipalities --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-trophy mr-1"></i> Top Municipalities</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        @forelse($topMunicipalities as $name => $count)
                            <li class="item">
                                <div class="product-info">
                                    <span class="product-title font-weight-bold">{{ $name }}</span>
                                    <span class="badge badge-primary float-right">{{ $count }} requests</span>
                                </div>
                            </li>
                        @empty
                            <li class="item">
                                <div class="product-info text-center text-muted py-3">
                                    No request data yet.
                                </div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Requests --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Recent Requests</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-outline-primary">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tracking Code</th>
                                <th>Citizen</th>
                                <th>Service</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $req)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.requests.show', $req->id) }}">
                                            <code>{{ $req->tracking_code }}</code>
                                        </a>
                                    </td>
                                    <td>{{ $req->user?->name ?? 'N/A' }}</td>
                                    <td>{{ $req->service?->name ?? 'N/A' }}</td>
                                    <td>{{ $req->service?->governmentOffice?->name ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'Pending' => 'warning',
                                                'In Review' => 'info',
                                                'Missing Documents' => 'orange',
                                                'Approved' => 'success',
                                                'Rejected' => 'danger',
                                                'Completed' => 'primary',
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $statusColors[$req->status] ?? 'secondary' }}">
                                            {{ $req->status }}
                                        </span>
                                    </td>
                                    <td>{{ $req->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No requests yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script type="application/json" id="admin-dashboard-chart-data">
        {
            "requestsTrendLabels": @json($requestsTrend->keys()->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))),
            "requestsTrendData": @json($requestsTrend->values()),
            "statusLabels": @json(array_keys($statusDistribution)),
            "statusData": @json(array_values($statusDistribution)),
            "revenueTrendLabels": @json($revenueTrend->keys()->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))),
            "revenueTrendData": @json($revenueTrend->values())
        }
    </script>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script>
        (() => {
            const callbacks = window.AdminLiveCallbacks = window.AdminLiveCallbacks || {};

            callbacks.dashboard = {
                init(region) {
                    if (!region) {
                        return;
                    }

                    const dataScript = region.querySelector('#admin-dashboard-chart-data');

                    if (!dataScript || typeof Chart === 'undefined') {
                        return;
                    }

                    const chartData = JSON.parse(dataScript.textContent);
                    const palette = {
                        blue: '#007bff',
                        green: '#28a745',
                        red: '#dc3545',
                        yellow: '#ffc107',
                        cyan: '#17a2b8',
                        orange: '#fd7e14',
                        gray: '#6c757d',
                    };
                    const statusColors = {
                        'Pending': palette.yellow,
                        'In Review': palette.cyan,
                        'Missing Documents': palette.orange,
                        'Approved': palette.green,
                        'Rejected': palette.red,
                        'Completed': palette.blue,
                    };
                    const charts = [];

                    charts.push(new Chart(region.querySelector('#requestsTrendChart'), {
                        type: 'line',
                        data: {
                            labels: chartData.requestsTrendLabels,
                            datasets: [{
                                label: 'New Requests',
                                data: chartData.requestsTrendData,
                                borderColor: palette.cyan,
                                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            scales: {
                                yAxes: [{
                                    ticks: { beginAtZero: true, precision: 0 },
                                    grid: { color: 'rgba(0,0,0,0.05)' }
                                }],
                                xAxes: [{
                                    grid: { display: false }
                                }]
                            },
                            plugins: { legend: { display: false } }
                        }
                    }));

                    charts.push(new Chart(region.querySelector('#statusDistChart'), {
                        type: 'doughnut',
                        data: {
                            labels: chartData.statusLabels,
                            datasets: [{
                                data: chartData.statusData,
                                backgroundColor: chartData.statusLabels.map((status) => statusColors[status] || palette.gray),
                                borderWidth: 2,
                                borderColor: '#fff',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            legend: {
                                position: 'bottom',
                                labels: { padding: 15, usePointStyle: true }
                            }
                        }
                    }));

                    charts.push(new Chart(region.querySelector('#revenueTrendChart'), {
                        type: 'line',
                        data: {
                            labels: chartData.revenueTrendLabels,
                            datasets: [{
                                label: 'Revenue ($)',
                                data: chartData.revenueTrendData,
                                borderColor: palette.green,
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            scales: {
                                yAxes: [{
                                    ticks: { beginAtZero: true },
                                    grid: { color: 'rgba(0,0,0,0.05)' }
                                }],
                                xAxes: [{
                                    grid: { display: false }
                                }]
                            },
                            plugins: { legend: { display: false } }
                        }
                    }));

                    region.__adminCharts = charts;
                },
                teardown(region) {
                    (region.__adminCharts || []).forEach((chart) => chart.destroy());
                    region.__adminCharts = [];
                }
            };
        })();
    </script>
@endpush
