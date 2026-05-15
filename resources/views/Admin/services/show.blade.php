@extends('layouts.admin')

@section('title', 'Service Details')
@section('page-title', 'Service Details')

@section('content')
    <div class="row">
        {{-- Service Info --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-concierge-bell mr-1"></i> {{ $service->name }}</h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-{{ $service->is_active ? 'secondary' : 'success' }} btn-sm btn-toggle-service"
                            data-url="{{ route('admin.services.toggle-status', $service) }}"
                            data-name="{{ $service->name }}"
                            data-action="{{ $service->is_active ? 'deactivate' : 'activate' }}">
                            <i class="fas fa-{{ $service->is_active ? 'ban' : 'check' }} mr-1"></i>
                            {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <a href="{{ route('admin.services.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Services
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="pl-0" style="width: 140px;">Category</th>
                                    <td>
                                        @if($service->serviceCategory)
                                            <span class="badge badge-info">{{ $service->serviceCategory->name }}</span>
                                        @else
                                            <span class="text-muted">Uncategorized</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Price</th>
                                    <td class="font-weight-bold text-success">{{ $service->formattedPrice() }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Duration</th>
                                    <td><i class="fas fa-clock mr-1 text-muted"></i> {{ $service->durationLabel() }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Status</th>
                                    <td>
                                        @if($service->is_active)
                                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="pl-0" style="width: 140px;">Office</th>
                                    <td>{{ $service->governmentOffice?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Municipality</th>
                                    <td>{{ $service->governmentOffice?->municipality?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Total Requests</th>
                                    <td><span class="badge badge-primary">{{ $stats['total_requests'] }}</span></td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Created</th>
                                    <td>{{ $service->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($service->description)
                        <hr>
                        <h6 class="font-weight-bold"><i class="fas fa-info-circle mr-1"></i> Description</h6>
                        <p class="text-muted">{{ $service->description }}</p>
                    @endif

                    @if($service->requiredDocumentList())
                        <hr>
                        <h6 class="font-weight-bold"><i class="fas fa-file-alt mr-1"></i> Required Documents</h6>
                        <ul class="list-unstyled">
                            @foreach($service->requiredDocumentList() as $doc)
                                <li class="mb-1"><i class="fas fa-check text-success mr-2"></i>{{ $doc }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Request Stats --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Request Statistics</h3>
                </div>
                <div class="card-body">
                    <canvas id="serviceStatsChart" height="200"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <tbody>
                            <tr>
                                <td><span class="badge badge-warning mr-1">&nbsp;</span> Pending</td>
                                <td class="text-right font-weight-bold">{{ $stats['pending'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-info mr-1">&nbsp;</span> In Review</td>
                                <td class="text-right font-weight-bold">{{ $stats['in_review'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success mr-1">&nbsp;</span> Approved</td>
                                <td class="text-right font-weight-bold">{{ $stats['approved'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-danger mr-1">&nbsp;</span> Rejected</td>
                                <td class="text-right font-weight-bold">{{ $stats['rejected'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-primary mr-1">&nbsp;</span> Completed</td>
                                <td class="text-right font-weight-bold">{{ $stats['completed'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Requests --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Recent Requests for this Service</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Tracking Code</th>
                        <th>Citizen</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequests as $req)
                        <tr>
                            <td><code>{{ $req->tracking_code }}</code></td>
                            <td>{{ $req->user?->name ?? 'N/A' }}</td>
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
                                <span class="badge badge-{{ $statusColors[$req->status] ?? 'secondary' }}">{{ $req->status }}</span>
                            </td>
                            <td>{{ $req->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('admin.requests.show', $req) }}" class="btn btn-outline-info btn-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No requests for this service yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Status distribution chart
        new Chart(document.getElementById('serviceStatsChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Review', 'Approved', 'Rejected', 'Completed'],
                datasets: [{
                    data: [
                        {{ $stats['pending'] }},
                        {{ $stats['in_review'] }},
                        {{ $stats['approved'] }},
                        {{ $stats['rejected'] }},
                        {{ $stats['completed'] }}
                    ],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#007bff'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Toastr config
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
            extendedTimeOut: 2000,
        };

        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif

        // SweetAlert2 toggle status
        $(document).on('click', '.btn-toggle-service', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');
            var action = $(this).data('action');

            var isActivate = action === 'activate';
            var title = isActivate ? 'Activate Service?' : 'Deactivate Service?';
            var html = isActivate
                ? 'Are you sure you want to activate <strong>' + name + '</strong>?'
                : 'Are you sure you want to deactivate <strong>' + name + '</strong>?';
            var icon = isActivate ? 'question' : 'warning';
            var confirmColor = isActivate ? '#28a745' : '#6c757d';
            var confirmText = isActivate ? 'Yes, activate' : 'Yes, deactivate';

            Swal.fire({
                title: title,
                html: html,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('<form>', {
                        method: 'POST',
                        action: url
                    });
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_method',
                        value: 'PATCH'
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    </script>
@endpush
