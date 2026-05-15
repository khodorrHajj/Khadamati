@extends('layouts.admin')

@section('title', 'Feedback Management')
@section('page-title', 'Feedback Management')

@section('content')
    {{-- Stats Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total Feedback</p>
                </div>
                <div class="icon"><i class="fas fa-comments"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['average_rating'] }} <i class="fas fa-star fa-sm"></i></h3>
                    <p>Average Rating</p>
                </div>
                <div class="icon"><i class="fas fa-star-half-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['responded'] }}</h3>
                    <p>Responded</p>
                </div>
                <div class="icon"><i class="fas fa-reply"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['unresponded'] }}</h3>
                    <p>Awaiting Response</p>
                </div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>
    </div>

    {{-- Rating Distribution --}}
    <div class="row mb-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Rating Distribution</h3>
                </div>
                <div class="card-body">
                    <canvas id="ratingChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-star mr-1"></i> Rating Breakdown</h3>
                </div>
                <div class="card-body pt-2">
                    @foreach(range(5, 1) as $r)
                        @php $count = $stats['rating_distribution'][$r] ?? 0; $pct = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0; @endphp
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-nowrap mr-2" style="width: 60px;">
                                {{ $r }} <i class="fas fa-star text-warning fa-sm"></i>
                            </span>
                            <div class="progress flex-grow-1 mr-2" style="height: 20px;">
                                <div class="progress-bar bg-{{ $r >= 4 ? 'success' : ($r >= 3 ? 'warning' : 'danger') }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-muted small" style="width: 50px;">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.feedback.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Rating</label>
                            <select name="rating" class="form-control select2-filter">
                                <option value="">All Ratings</option>
                                @foreach(range(1, 5) as $r)
                                    <option value="{{ $r }}" {{ request('rating') == $r ? 'selected' : '' }}>{{ $r }} Star{{ $r > 1 ? 's' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Response Status</label>
                            <select name="response_status" class="form-control select2-filter">
                                <option value="">All</option>
                                <option value="responded" {{ request('response_status') === 'responded' ? 'selected' : '' }}>Responded</option>
                                <option value="unresponded" {{ request('response_status') === 'unresponded' ? 'selected' : '' }}>Awaiting Response</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Municipality</label>
                            <select name="municipality" class="form-control select2-filter">
                                <option value="">All</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" {{ request('municipality') == $municipality->id ? 'selected' : '' }}>{{ $municipality->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Office</label>
                            <select name="office" class="form-control select2-filter">
                                <option value="">All</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ request('office') == $office->id ? 'selected' : '' }}>{{ $office->municipality?->name ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Comment or user...">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-3">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
                            @if(request()->hasAny(['rating', 'response_status', 'municipality', 'office', 'search']))
                                <a href="{{ route('admin.feedback.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Feedback Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-comments mr-1"></i> All Feedback</h3>
            <span class="text-muted small">{{ $feedbacks->total() }} feedback entries</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 90px;">Rating</th>
                        <th>Citizen</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Comment</th>
                        <th style="width: 110px;">Status</th>
                        <th>Date</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feedbacks as $fb)
                        <tr>
                            <td>
                                @foreach(range(1, 5) as $star)
                                    <i class="fas fa-star {{ $star <= $fb->rating ? 'text-warning' : 'text-muted' }}" style="font-size: 0.75rem;"></i>
                                @endforeach
                            </td>
                            <td class="font-weight-bold">{{ $fb->user?->name ?? 'N/A' }}</td>
                            <td>{{ $fb->serviceRequest?->service?->name ?? 'N/A' }}</td>
                            <td>{{ $fb->serviceRequest?->service?->governmentOffice?->name ?? 'N/A' }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $fb->comment }}">
                                    {{ Str::limit($fb->comment, 60) }}
                                </div>
                            </td>
                            <td>
                                @if($fb->responded_at)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Responded</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $fb->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.feedback.show', $fb) }}" class="btn btn-outline-info btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-comments fa-2x mb-2 d-block text-muted"></i>
                                No feedback found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($feedbacks->hasPages())
            <div class="card-footer">
                {{ $feedbacks->links() }}
            </div>
        @endif
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
        // Rating distribution chart
        new Chart(document.getElementById('ratingChart'), {
            type: 'bar',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Feedback Count',
                    data: [
                        {{ $stats['rating_distribution'][1] ?? 0 }},
                        {{ $stats['rating_distribution'][2] ?? 0 }},
                        {{ $stats['rating_distribution'][3] ?? 0 }},
                        {{ $stats['rating_distribution'][4] ?? 0 }},
                        {{ $stats['rating_distribution'][5] ?? 0 }}
                    ],
                    backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#007bff'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
                },
                plugins: { legend: { display: false } }
            }
        });

        // Select2
        $('.select2-filter').select2({ theme: 'bootstrap4', width: '100%' });

        // Toastr
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 4000 };
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
    </script>
@endpush
