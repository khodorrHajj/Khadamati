@extends('layouts.admin')

@section('title', 'Services Overview')
@section('page-title', 'Services Overview')

@section('content')
    {{-- Filter Card --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.services.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Municipality</label>
                            <select name="municipality" class="form-control select2-filter">
                                <option value="">All Municipalities</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" {{ request('municipality') == $municipality->id ? 'selected' : '' }}>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Government Office</label>
                            <select name="office" class="form-control select2-filter">
                                <option value="">All Offices</option>
                                @foreach ($offices as $office)
                                    <option value="{{ $office->id }}" {{ request('office') == $office->id ? 'selected' : '' }}>
                                        {{ $office->municipality?->name ? $office->municipality->name . ' - ' : '' }}{{ $office->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control select2-filter">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control select2-filter">
                                <option value="">All</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-3">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
                            @if(request()->hasAny(['municipality', 'office', 'category', 'status', 'search']))
                                <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Services Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-concierge-bell mr-1"></i> All Services</h3>
            <span class="text-muted small">{{ $services->total() }} services found</span>
        </div>

        <div class="card-body p-0">
            <table id="servicesTable" class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Office</th>
                        <th>Municipality</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Requests</th>
                        <th>Status</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $service->name }}</div>
                                @if($service->description)
                                    <div class="text-muted small text-truncate" style="max-width: 200px;" title="{{ $service->description }}">
                                        {{ Str::limit($service->description, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($service->serviceCategory)
                                    <span class="badge badge-info">{{ $service->serviceCategory->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $service->governmentOffice?->name ?? '—' }}</td>
                            <td>{{ $service->governmentOffice?->municipality?->name ?? '—' }}</td>
                            <td class="font-weight-bold">{{ $service->formattedPrice() }}</td>
                            <td>
                                <span class="text-muted"><i class="fas fa-clock fa-xs mr-1"></i>{{ $service->durationLabel() }}</span>
                            </td>
                            <td>
                                @php $reqCount = $service->serviceRequests()->count(); @endphp
                                <span class="badge badge-{{ $reqCount > 0 ? 'primary' : 'secondary' }}">{{ $reqCount }}</span>
                            </td>
                            <td>
                                @if($service->is_active)
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.services.show', $service) }}" class="btn btn-outline-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-{{ $service->is_active ? 'secondary' : 'success' }} btn-toggle-service"
                                        data-url="{{ route('admin.services.toggle-status', $service) }}"
                                        data-name="{{ $service->name }}"
                                        data-action="{{ $service->is_active ? 'deactivate' : 'activate' }}"
                                        title="{{ $service->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="fas fa-{{ $service->is_active ? 'ban' : 'check' }}"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-concierge-bell fa-2x mb-2 d-block text-muted"></i>
                                No services found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="card-footer">
                {{ $services->links() }}
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        // Initialize DataTable (client-side on paginated data)
        $('#servicesTable').DataTable({
            responsive: true,
            autoWidth: false,
            paging: false, // Server-side pagination already handled
            searching: false, // We have our own filters
            info: false,
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });

        // Initialize Select2 on filters
        $('.select2-filter').select2({
            theme: 'bootstrap4',
            width: '100%'
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
                : 'Are you sure you want to deactivate <strong>' + name + '</strong>? Citizens will no longer be able to request this service.';
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
