@extends('layouts.admin')

@section('title', 'Manage Government Offices')
@section('page-title', 'Manage Government Offices')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-building mr-1"></i> Government Offices</h3>
            <a href="{{ route('admin.offices.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Government Office
            </a>
        </div>

        <div class="card-body p-0">
            <table id="officesTable" class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Office Name</th>
                        <th>Municipality</th>
                        <th>Service Type</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offices as $office)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $office->name }}</div>
                            </td>
                            <td>{{ $office->municipality->name ?? 'No Municipality' }}</td>
                            <td>{{ $office->service_type ?? '-' }}</td>
                            <td>
                                @if ($office->phone)
                                    <div><i class="fas fa-phone fa-xs mr-1 text-muted"></i> {{ $office->phone }}</div>
                                @endif
                                @if ($office->email)
                                    <div class="small"><i class="fas fa-envelope fa-xs mr-1 text-muted"></i> {{ $office->email }}</div>
                                @endif
                                @if (!$office->phone && !$office->email)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($office->status === 'active')
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-pause-circle mr-1"></i>Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('admin.offices.show', $office) }}" class="dropdown-item">
                                            <i class="fas fa-eye mr-2 text-info"></i> View Details
                                        </a>
                                        <a href="{{ route('admin.offices.edit', $office) }}" class="dropdown-item">
                                            <i class="fas fa-edit mr-2 text-warning"></i> Edit Office
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item text-danger btn-delete-office"
                                            data-url="{{ route('admin.offices.destroy', $office) }}"
                                            data-name="{{ $office->name }}">
                                            <i class="fas fa-trash mr-2"></i> Delete Office
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-building fa-2x mb-2 d-block text-muted"></i>
                                No government offices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte/plugins/toastr/toastr.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Initialize DataTable
        $('#officesTable').DataTable({
            responsive: true,
            autoWidth: false,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            pageLength: 10,
            language: {
                search: '<i class="fas fa-search"></i>',
                searchPlaceholder: 'Search offices...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ offices',
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                }
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
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

        // SweetAlert2 delete confirmation
        $(document).on('click', '.btn-delete-office', function() {
            var url = $(this).data('url');
            var name = $(this).data('name');

            Swal.fire({
                title: 'Delete Government Office?',
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
