@extends('layouts.admin')

@section('title', 'Citizen Details')
@section('page-title', 'Citizen Details')

@section('content')
    <div class="row">
        {{-- Citizen Info --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-user mr-1"></i> {{ $citizen->name }}</h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-{{ $citizen->status === 'active' ? 'secondary' : 'success' }} btn-sm btn-toggle-citizen"
                            data-url="{{ $citizen->status === 'active' ? route('admin.citizens.deactivate', $citizen) : route('admin.citizens.activate', $citizen) }}"
                            data-name="{{ $citizen->name }}"
                            data-action="{{ $citizen->status === 'active' ? 'deactivate' : 'activate' }}">
                            <i class="fas fa-{{ $citizen->status === 'active' ? 'ban' : 'check' }} mr-1"></i>
                            {{ $citizen->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete-citizen"
                            data-url="{{ route('admin.citizens.destroy', $citizen) }}"
                            data-name="{{ $citizen->name }}">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                        <a href="{{ route('admin.citizens.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="pl-0" style="width: 130px;">ID</th>
                                    <td>#{{ $citizen->id }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Name</th>
                                    <td class="font-weight-bold">{{ $citizen->name }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Email</th>
                                    <td>
                                        <a href="mailto:{{ $citizen->email }}">{{ $citizen->email }}</a>
                                        @if($citizen->email_verified_at)
                                            <i class="fas fa-check-circle text-success ml-1" title="Verified"></i>
                                        @else
                                            <i class="fas fa-times-circle text-warning ml-1" title="Not Verified"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Phone</th>
                                    <td>{{ $citizen->phone ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="pl-0" style="width: 130px;">Status</th>
                                    <td>
                                        @if($citizen->status === 'active')
                                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="pl-0">2FA Enabled</th>
                                    <td>
                                        @if($citizen->two_factor_enabled)
                                            <span class="badge badge-info"><i class="fas fa-shield-alt mr-1"></i>Enabled</span>
                                        @else
                                            <span class="badge badge-light text-muted">Disabled</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Registered</th>
                                    <td>{{ $citizen->created_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="pl-0">Last Updated</th>
                                    <td>{{ $citizen->updated_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
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
                    <canvas id="citizenStatsChart" height="200"></canvas>
                    <table class="table table-sm mt-3 mb-0">
                        <tbody>
                            <tr>
                                <td><span class="badge badge-warning mr-1">&nbsp;</span> Pending</td>
                                <td class="text-right font-weight-bold">{{ $requestStats['pending'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-info mr-1">&nbsp;</span> In Review</td>
                                <td class="text-right font-weight-bold">{{ $requestStats['in_review'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success mr-1">&nbsp;</span> Approved</td>
                                <td class="text-right font-weight-bold">{{ $requestStats['approved'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-danger mr-1">&nbsp;</span> Rejected</td>
                                <td class="text-right font-weight-bold">{{ $requestStats['rejected'] }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-primary mr-1">&nbsp;</span> Completed</td>
                                <td class="text-right font-weight-bold">{{ $requestStats['completed'] }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold border-top">
                                <td>Total</td>
                                <td class="text-right">{{ $requestStats['total'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Requests --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-signature mr-1"></i> Recent Requests</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Tracking Code</th>
                        <th>Service</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequests as $req)
                        <tr>
                            <td><code>{{ $req->tracking_code }}</code></td>
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
                            <td colspan="6" class="text-center text-muted py-3">No requests from this citizen yet.</td>
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
        // Stats chart
        new Chart(document.getElementById('citizenStatsChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Review', 'Approved', 'Rejected', 'Completed'],
                datasets: [{
                    data: [
                        {{ $requestStats['pending'] }},
                        {{ $requestStats['in_review'] }},
                        {{ $requestStats['approved'] }},
                        {{ $requestStats['rejected'] }},
                        {{ $requestStats['completed'] }}
                    ],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#007bff'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } }
            }
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

        // SweetAlert2 toggle
        $(document).on('click', '.btn-toggle-citizen', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');
            var action = $(this).data('action');
            var isActivate = action === 'activate';

            Swal.fire({
                title: isActivate ? 'Activate Citizen?' : 'Deactivate Citizen?',
                html: isActivate
                    ? 'Are you sure you want to activate <strong>' + name + '</strong>?'
                    : 'Are you sure you want to deactivate <strong>' + name + '</strong>?',
                icon: isActivate ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isActivate ? '#28a745' : '#6c757d',
                cancelButtonColor: '#6c757d',
                confirmButtonText: isActivate ? 'Yes, activate' : 'Yes, deactivate',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('<form>', { method: 'POST', action: url });
                    form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
                    form.append($('<input>', { type: 'hidden', name: '_method', value: 'PATCH' }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // SweetAlert2 delete
        $(document).on('click', '.btn-delete-citizen', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');

            Swal.fire({
                title: 'Delete Citizen?',
                html: 'Are you sure you want to delete <strong>' + name + '</strong>?<br>This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('<form>', { method: 'POST', action: url });
                    form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
                    form.append($('<input>', { type: 'hidden', name: '_method', value: 'DELETE' }));
                    $('body').append(form);
                    form.submit();
                }
            });
        });
    </script>
@endpush

