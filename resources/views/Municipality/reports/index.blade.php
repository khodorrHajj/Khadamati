@extends('layouts.municipality')

@section('title', 'Reports')
@section('page-title', 'Office Reports')

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $requests->count() }}</h3>
                    <p>Total Requests</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($revenueTotal, 2) }}</h3>
                    <p>Revenue</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format((float) $averageCompletionHours, 1) }}</h3>
                    <p>Avg Completion Hours</p>
                </div>
                <div class="icon"><i class="fas fa-stopwatch"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $appointmentSummary['approved'] }}</h3>
                    <p>Approved Appointments</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Requests by Status</h3></div>
                <div class="card-body">
                    <canvas id="municipality-status-chart" height="180"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <tbody>
                            @foreach ($statusData as $row)
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
                <div class="card-header"><h3 class="card-title">Monthly Requests</h3></div>
                <div class="card-body">
                    <canvas id="municipality-monthly-chart" height="180"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <tbody>
                            @foreach ($monthlyData as $row)
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
                <div class="card-header"><h3 class="card-title">Requests by Service</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($serviceData as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No service data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Requests by Category</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categoryData as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">No category data yet.</td>
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
        new Chart(document.getElementById('municipality-status-chart'), {
            type: 'doughnut',
            data: {
                labels: @json($statusData->pluck('label')->values()),
                datasets: [{
                    data: @json($statusData->pluck('count')->values()),
                    backgroundColor: ['#17a2b8', '#007bff', '#ffc107', '#28a745', '#6c757d', '#dc3545']
                }]
            }
        });

        new Chart(document.getElementById('municipality-monthly-chart'), {
            type: 'line',
            data: {
                labels: @json($monthlyData->pluck('label')->values()),
                datasets: [{
                    label: 'Requests',
                    data: @json($monthlyData->pluck('count')->values()),
                    borderColor: '#007bff',
                    fill: false
                }]
            }
        });
    </script>
@endpush
