@extends('layouts.municipality')

@section('title', 'Reports')
@section('page-title', 'Office Reports')

@section('content')
    {{-- Stat Cards --}}
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
                    <p>Avg Hours</p>
                </div>
                <div class="icon"><i class="fas fa-stopwatch"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $appointmentSummary['approved'] }}</h3>
                    <p>Approved Appts</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Requests by Status</h3>
                </div>
                <div class="card-body">
                    <canvas id="municipality-status-chart" height="200"></canvas>
                    <table class="table table-bordered table-sm mt-3">
                        @forelse ($statusData as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-right"><strong>{{ $row['count'] }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No status data yet.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Monthly Requests</h3>
                </div>
                <div class="card-body">
                    <canvas id="municipality-monthly-chart" height="200"></canvas>
                    <table class="table table-bordered table-sm mt-3">
                        @forelse ($monthlyData as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-right"><strong>{{ $row['count'] }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No monthly data yet.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Tables Row --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-concierge-bell mr-1"></i> Requests by Service</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped mb-0">
                        @forelse ($serviceData as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-right"><strong>{{ $row['count'] }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">No service data yet.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Requests by Category</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped mb-0">
                        @forelse ($categoryData as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-right"><strong>{{ $row['count'] }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">No category data yet.</td>
                            </tr>
                        @endforelse
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
                    backgroundColor: ['#4299e1', '#2b6cb0', '#ecc94b', '#48bb78', '#a0aec0', '#fc8181']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                }
            }
        });

        new Chart(document.getElementById('municipality-monthly-chart'), {
            type: 'line',
            data: {
                labels: @json($monthlyData->pluck('label')->values()),
                datasets: [{
                    label: 'Requests',
                    data: @json($monthlyData->pluck('count')->values()),
                    borderColor: '#2b6cb0',
                    backgroundColor: 'rgba(43, 108, 176, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#2b6cb0',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    </script>
@endpush