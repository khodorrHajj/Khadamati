@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Platform Reports')

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
            <h3 class="card-title">Revenue Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Municipality</label>
                            <select name="municipality" class="custom-select">
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
                            <select name="office" class="custom-select">
                                <option value="">All Offices</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ (string) $filters['office'] === (string) $office->id ? 'selected' : '' }}>
                                        {{ $office->name }}
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
                            <button type="submit" class="btn btn-primary">Apply</button>
                            @if ($filters['municipality'] || $filters['office'] || $filters['date_from'] || $filters['date_to'])
                                <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($revenueTotal, 2) }}</h3>
                    <p>Filtered Revenue</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
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

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Requests per Government Office</h3></div>
                <div class="card-body">
                    <canvas id="admin-office-chart" height="180"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <tbody>
                            @foreach ($requestsPerOffice as $row)
                                <tr>
                                    <th>{{ $row['label'] }}</th>
                                    <td>{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Top Services</h3></div>
                <div class="card-body">
                    <canvas id="admin-service-chart" height="180"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <tbody>
                            @foreach ($topServices as $row)
                                <tr>
                                    <th>{{ $row['label'] }}</th>
                                    <td>{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Revenue by Municipality and Office</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Municipality</th>
                                <th>Office</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($revenueRows as $row)
                                <tr>
                                    <td>{{ $row['municipality'] }}</td>
                                    <td>{{ $row['office'] }}</td>
                                    <td>${{ number_format($row['revenue'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No revenue data for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Pending Workload</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Office</th>
                                <th>Pending Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingWorkload as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No pending workload found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script>
        new Chart(document.getElementById('admin-office-chart'), {
            type: 'bar',
            data: {
                labels: @json($requestsPerOffice->pluck('label')->values()),
                datasets: [{
                    label: 'Requests',
                    data: @json($requestsPerOffice->pluck('count')->values()),
                    backgroundColor: '#17a2b8'
                }]
            }
        });

        new Chart(document.getElementById('admin-service-chart'), {
            type: 'bar',
            data: {
                labels: @json($topServices->pluck('label')->values()),
                datasets: [{
                    label: 'Requests',
                    data: @json($topServices->pluck('count')->values()),
                    backgroundColor: '#ffc107'
                }]
            }
        });
    </script>
@endpush
