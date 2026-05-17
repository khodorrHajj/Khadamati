@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Platform Reports')

@section('content')
    {{-- Filter Card --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-filter mr-1"></i> Report Filters</h3>
            <div class="btn-group btn-group-sm">
                <a href="#" id="btn-export-pdf" class="btn btn-danger" title="Export as PDF">
                    <i class="fas fa-file-pdf mr-1"></i> PDF
                </a>
                <a href="#" id="btn-export-csv" class="btn btn-success" title="Export as CSV">
                    <i class="fas fa-file-csv mr-1"></i> CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <form id="report-filter-form" method="GET" action="{{ route('admin.reports.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Municipality</label>
                            <select name="municipality" class="form-control select2-filter">
                                <option value="">All Municipalities</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" {{ (string) $filters['municipality'] === (string) $municipality->id ? 'selected' : '' }}>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Office</label>
                            <select name="office" class="form-control select2-filter">
                                <option value="">All Offices</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ (string) $filters['office'] === (string) $office->id ? 'selected' : '' }}>
                                        {{ $office->municipality?->name ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>From</label>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>To</label>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="btn-group mb-3 w-100">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Apply</button>
                            @if ($filters['municipality'] || $filters['office'] || $filters['date_from'] || $filters['date_to'])
                                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>LBP {{ number_format($revenueTotal, 0) }}</h3>
                    <p>Filtered Revenue</p>
                </div>
                <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $requestsPerOffice->count() }}</h3>
                    <p>Offices with Requests</p>
                </div>
                <div class="icon"><i class="fas fa-building"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $topServices->first()['label'] ?? 'N/A' }}</h3>
                    <p>Top Service</p>
                </div>
                <div class="icon"><i class="fas fa-award"></i></div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-building mr-1"></i> Requests per Government Office</h3></div>
                <div class="card-body">
                    <canvas id="admin-office-chart" height="180"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <thead><tr><th>Office</th><th class="text-right">Count</th></tr></thead>
                        <tbody>
                            @foreach ($requestsPerOffice as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-right">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-award mr-1"></i> Top Services</h3></div>
                <div class="card-body">
                    <canvas id="admin-service-chart" height="180"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <thead><tr><th>Service</th><th class="text-right">Count</th></tr></thead>
                        <tbody>
                            @foreach ($topServices as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-right">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tables Row --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-coins mr-1"></i> Revenue by Municipality & Office</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Municipality</th>
                                <th>Office</th>
                                <th class="text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($revenueRows as $row)
                                <tr>
                                    <td>{{ $row['municipality'] }}</td>
                                    <td>{{ $row['office'] }}</td>
                                    <td class="text-right font-weight-bold">LBP {{ number_format($row['revenue'], 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No revenue data for the selected filters.</td>
                                </tr>
                            @endforelse
                            @if($revenueRows->isNotEmpty())
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="2" class="text-right">Total</td>
                                    <td class="text-right">LBP {{ number_format($revenueTotal, 0) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-clock mr-1"></i> Pending Workload</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Office</th>
                                <th class="text-right">Pending Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingWorkload as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-{{ $row['count'] > 10 ? 'danger' : ($row['count'] > 5 ? 'warning' : 'success') }}">
                                            {{ $row['count'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No pending workload found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Select2
        $('.select2-filter').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Toastr
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
        };
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif

        // Export buttons - carry current filters
        function getFilterParams() {
            var form = $('#report-filter-form');
            var params = form.serialize();
            return params ? '&' + params : '';
        }

        $('#btn-export-pdf').on('click', function(e) {
            e.preventDefault();
            var params = getFilterParams();
            window.location.href = '{{ route('admin.reports.export.pdf') }}?' + params;
        });

        $('#btn-export-csv').on('click', function(e) {
            e.preventDefault();
            var params = getFilterParams();
            window.location.href = '{{ route('admin.reports.export.csv') }}?' + params;
        });

        // Charts
        new Chart(document.getElementById('admin-office-chart'), {
            type: 'bar',
            data: {
                labels: @json($requestsPerOffice->pluck('label')->values()),
                datasets: [{
                    label: 'Requests',
                    data: @json($requestsPerOffice->pluck('count')->values()),
                    backgroundColor: 'rgba(23, 162, 184, 0.7)',
                    borderColor: '#17a2b8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
            }
        });

        new Chart(document.getElementById('admin-service-chart'), {
            type: 'bar',
            data: {
                labels: @json($topServices->pluck('label')->values()),
                datasets: [{
                    label: 'Requests',
                    data: @json($topServices->pluck('count')->values()),
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
            }
        });
    </script>
@endpush
